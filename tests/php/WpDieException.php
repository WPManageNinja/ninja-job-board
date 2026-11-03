<?php

namespace NinjaJobBoard\Tests;

/**
 * Thrown instead of exiting when code under test calls wp_die() (directly or via wp_send_json())
 */
class WpDieException extends \RuntimeException
{
}
