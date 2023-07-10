<?php declare(strict_types = 1);

namespace EduSharing;

use Exception;

/**
 * Class UsageDeletedException
 *
 * to be thrown when the deletion of a usage fails (response code 403)
 *
 * @author Torsten Simon  <simon@edu-sharing.net>
 */
class UsageDeletedException extends Exception {}