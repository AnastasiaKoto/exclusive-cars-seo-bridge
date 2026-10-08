<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use DomainException;
use ExclusiveCars\SeoBridge\Exception\VersionMismatchException;
use ExclusiveCars\SeoBridge\Write\PostUpdater;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PostUpdateController
{
    public function __construct(private PostUpdater $postUpdater)
    {
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $postId = (int) $request->get_param('post_id');
            if (strlen($request->get_body()) > 1048576) {
                throw new InvalidArgumentException('Request body exceeds 1 MiB.');
            }

            $fields = $request->get_param('fields');
            $expectedVersion = $request->get_param('expected_version');

            if (!is_string($expectedVersion)
                || !preg_match('/^sha256:[a-f0-9]{64}$/', $expectedVersion)
                || !is_array($fields)) {
                throw new InvalidArgumentException('Invalid write request.');
            }
            
            return new WP_REST_Response(
                $this->postUpdater->update($postId, $expectedVersion, $fields),
                200
            );
        } catch (VersionMismatchException $error) {
            return new WP_Error('version_conflict', $error->getMessage(), ['status' => 409]);
        } catch (DomainException $error) {
            return new WP_Error('field_not_writable', $error->getMessage(), ['status' => 422]);
        } catch (InvalidArgumentException $error) {
            $status = $error->getCode() === 404 ? 404 : 400;
            return new WP_Error(
                $status === 404 ? 'object_not_found' : 'invalid_request',
                $error->getMessage(),
                ['status' => $status]
            );
        } catch (RuntimeException $error) {
            if ($error->getCode() === 424) {
                return new WP_Error('dependency_unavailable', $error->getMessage(), ['status' => 424]);
            }
            error_log($error->__toString());
            return new WP_Error('apply_failed', $error->getMessage(), ['status' => 500]);
        } catch (Throwable $error) {
            error_log($error->__toString());
            return new WP_Error('apply_failed', 'An internal error occurred.', ['status' => 500]);
        }
    }
}
