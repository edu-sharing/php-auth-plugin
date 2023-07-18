<?php declare(strict_types = 1);

namespace EduSharingApiClient;

use Exception;

/**
 * Class NodeDeletedException
 *
 * to be thrown when the deletion of a node fails (response code 404)
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class NodeDeletedException extends Exception {}