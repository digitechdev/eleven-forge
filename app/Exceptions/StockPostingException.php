<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A stock posting was refused, e.g. it would take stock below zero or reverse an entry twice.
 * The message is safe to show to the user.
 */
class StockPostingException extends RuntimeException {}
