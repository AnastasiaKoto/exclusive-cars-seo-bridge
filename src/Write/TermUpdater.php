<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Write;

use ExclusiveCars\SeoBridge\Content\TermStateCollector;
use ExclusiveCars\SeoBridge\Contract\FieldGroups;
use ExclusiveCars\SeoBridge\Contract\TermFields;
use ExclusiveCars\SeoBridge\Inventory\TaxonomyCollector;
use WP_Error;
use WP_Term;
use RuntimeException;
use InvalidArgumentException;
use DomainException;
use Throwable;
use ExclusiveCars\SeoBridge\Exception\VersionMismatchException;

final class TermUpdater
{
    public function __construct(
        private TaxonomyCollector $taxonomyCollector,
        private CoreTermFieldWriter $coreFieldWriter,
        private YoastTermFieldWriter $yoastTermFieldWriter,
        private TermStateRestorer $termStateRestorer,
        private TermStateCollector $termStateCollector
    )
    {}

    public function update(int $termId, string $termTaxonomy, string $expectedVersion, array $fields): array
    {
        $term = $this->getTerm($termId, $termTaxonomy);
        $before = $this->getTermState($term);

        if (!hash_equals($before[TermFields::VERSION], $expectedVersion)) {
            throw new VersionMismatchException('Term version mismatch.');
        }
        if (empty($fields) || array_diff(array_keys($fields), FieldGroups::TERM_WRITABLE)) {
            throw new InvalidArgumentException('Fields must contain supported, non-empty groups.');
        }

        $core = $this->group($fields, FieldGroups::CORE);
        $yoast = $this->group($fields, FieldGroups::YOAST);
        $this->coreFieldWriter->validate($core);
        $this->yoastTermFieldWriter->validate($yoast);

        if ($core === [] && $yoast === []) {
            throw new InvalidArgumentException('At least one field must be provided.');
        }

        $requested = [FieldGroups::CORE => $core, FieldGroups::YOAST => $yoast];
        $changed = [];
        foreach ($requested as $group => $values) {
            foreach ($values as $key => $value) {
                $current = $group === FieldGroups::YOAST
                    ? ($before[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : $before[FieldGroups::CORE][$key];
                if ($current !== $value) {
                    $changed[$group][$key] = $value;
                }
            }
        }

        if ($changed === []) {
            return [
                'status' => 'no_change',
                'before' => $before,
                'after' => $before,
                'new_version' => $before[TermFields::VERSION],
                'verification' => 'matched',
                'compensation' => 'not_needed',
            ];
        }

        try {
            if (isset($changed[FieldGroups::CORE])) {
                $this->coreFieldWriter->write($term, $changed[FieldGroups::CORE]);
            }
            if (isset($changed[FieldGroups::YOAST])) {
                $this->yoastTermFieldWriter->write($term, $changed[FieldGroups::YOAST]);
            }
            $updatedTerm = $this->getTerm($termId, $termTaxonomy);
            $after = $this->getTermState($updatedTerm);
            
            if (!$this->matches($after, $requested)) {
                throw new RuntimeException('Read-back verification failed.');
            }
        } catch (Throwable $error) {
            try {
                $this->termStateRestorer->restore($term, $before, $changed);
                $updatedTerm = $this->getTerm($termId, $termTaxonomy);
                $restored = $this->getTermState($updatedTerm);
                $compensation = $this->matches($restored, $this->originalValues($before, $changed))
                    ? 'restored' : 'mismatched';
            } catch (Throwable $restoreError) {
                $compensation = 'failed';
            }
            throw new RuntimeException('Apply failed; compensation: ' . $compensation, 0, $error);
        }

        return [
            'status' => 'applied',
            'before' => $before,
            'after' => $after,
            'new_version' => $after[TermFields::VERSION],
            'verification' => 'matched',
            'compensation' => 'not_needed',
        ];
    }

    private function matches(array $state, array $values): bool
    {
        foreach ($values as $group => $fields) {
            foreach ($fields as $key => $value) {
                $actual = $group === FieldGroups::YOAST
                    ? ($state[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : ($state[FieldGroups::CORE][$key] ?? null);
                if ($actual !== $value) {
                    return false;
                }
            }
        }
        return true;
    }

    private function group(array $fields, string $name): array
    {
        $group = $fields[$name] ?? [];
        if (!is_array($group)) {
            throw new InvalidArgumentException($name . ' fields must be an object.');
        }
        return $group;
    }

    private function originalValues(array $before, array $changed): array
    {
        $values = [];
        foreach ($changed as $group => $fields) {
            foreach ($fields as $key => $_) {
                $values[$group][$key] = $group === FieldGroups::YOAST
                    ? ($before[FieldGroups::YOAST][$key]['raw'] ?? null)
                    : $before[FieldGroups::CORE][$key];
            }
        }
        return $values;
    }

    private function getTerm(int $termId, string $termTaxonomy): WP_Term
    {
        if (!in_array($termTaxonomy, $this->taxonomyCollector->names(), true)) {
            throw new DomainException('Taxonomy is not supported.');
        }

        $term = get_term($termId, $termTaxonomy);
        
        if (is_wp_error($term)) {
            throw new RuntimeException($term->get_error_message());
        }
        if (!$term instanceof WP_Term) {
            throw new InvalidArgumentException('Term not found.', 404);
        }
        return $term;
    }

    private function getTermState(WP_Term $term): array
    {
        return $this->termStateCollector->collect($term);
    }
}