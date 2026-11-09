<?php

namespace App\Enums;

enum InnovationStatus: string
{
    case Concept = 'CONCEPT';
    case Development = 'DEVELOPMENT';
    case Prototype = 'PROTOTYPE';
    case Testing = 'TESTING';
    case Completed = 'COMPLETED';
    case Deployed = 'DEPLOYED';
    case Retired = 'RETIRED';
}
