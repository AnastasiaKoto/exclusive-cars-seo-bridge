<?php
declare(strict_types=1);

namespace ExclusiveCars\SeoBridge\Rest;

use DomainException;
use ExclusiveCars\SeoBridge\Write\PostCreator;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class PostCreatorController
{
    public function __construct(private PostCreator $postCreator)
    {
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            if (strlen($request->get_body()) > 1048576) {
                throw new InvalidArgumentException('Request body exceeds 1 MiB.');
            }

            $fields = $request->get_param('fields');
            $postType = $request->get_param('post_type');
            
            if (!is_string($postType) || $postType === '' || !is_array($fields)) {
                throw new InvalidArgumentException('Invalid create request.');
            }

            return new WP_REST_Response(
                $this->postCreator->create($postType, $fields),
                201
            );
        }  catch (DomainException $error) {
            return new WP_Error('field_not_writable', $error->getMessage(), ['status' => 422]);
        } catch (InvalidArgumentException $error) {
            return new WP_Error('invalid_request', $error->getMessage(), ['status' => 400]);
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
