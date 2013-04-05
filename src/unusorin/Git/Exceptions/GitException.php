<?php
/**
 * src/unusorin/Git/Exceptions/GitException.php
 * @author Sorin Badea <sorin.badea91@gmail.com>
 */
namespace unusorin\Git\Exceptions;

/**
 * Class GitException
 * @package unusorin\Git\Exceptions
 */
class GitException extends \Exception
{
    const INSTALLATION_NOT_FOUND = 1;
    const INIT                   = 2;
    const REMOTES_LIST           = 3;
    const LOG_LIST               = 4;
    const BRANCH_LIST            = 5;
    const TAG_LISt               = 6;
    const FETCH                  = 7;
    const PULL                   = 8;
    const PUSH                   = 10;

}