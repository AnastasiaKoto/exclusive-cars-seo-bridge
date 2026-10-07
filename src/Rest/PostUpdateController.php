<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use ExclusiveCars\SeoBridge\Write\PostUpdater;
use WP_REST_Request;
use WP_REST_Response;
use InvalidArgumentException;
use WP_Error;
use ExclusiveCars\SeoBridge\Exception\VersionMismatchException;
use DomainException;
use Throwable;

final class PostUpdateController {
    public function __construct(
        private PostUpdater $postUpdater
    ){}

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post_id = (int) $request->get_param('post_id');
        $fields = $request->get_param('fields');
        $expectedVersion = $request->get_param('expected_version');

        if (!is_string($expectedVersion) || $expectedVersion === '') {
            throw new InvalidArgumentException(
                'Expected version must be a non-empty string.'
            );
        }

        if (!is_array($fields)) {
            throw new InvalidArgumentException(
                'Fields must be an array.'
            );
        }

        try {
            $snapshots = $this->postUpdater->update($post_id, $expectedVersion, $fields);
            return new WP_REST_Response($snapshots, 200);
        } catch(VersionMismatchException $exception) {
            return new WP_Error(
                'seo_bridge_version_conflict',
                $exception->getMessage(),
                ['status' => 409]
            );
        } catch (DomainException $exception) {
            return new WP_Error(
                'seo_bridge_operation_forbidden',
                $exception->getMessage(),
                ['status' => 403]
            );
        } catch (InvalidArgumentException $exception) {
            return new WP_Error(
                'seo_bridge_invalid_request',
                $exception->getMessage(),
                ['status' => 400]
            );
        } catch (Throwable $exception) {
            error_log($exception->__toString());

            return new WP_Error(
                'seo_bridge_internal_error',
                'An internal error occurred.',
                ['status' => 500]
            );
        }
    }
}