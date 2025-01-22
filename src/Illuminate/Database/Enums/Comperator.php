<?php

namespace Illuminate\Database\Enums;

enum Comperator: string
{
    case Equals = '=';
    case LessThan = '<';
    case GreaterThan = '>';
    case LessThanOrEqual = '<=';
    case GreaterThanOrEqual = '>=';
    case NotEqual = '!=';
    case AnsiNotEqual = '<>'; // same as Comperator::NotEqual
    case Spaceship = '<=>'; // null-safe equal
    case Like = 'like';
    case LikeBinary = 'like binary';
    case NotLike = 'not like';
    case CaseInsensitiveLike = 'ilike';
    case BitwiseAnd = '&';
    case BitwiseOr = '|';
    case Caret = '^';
    case BitwiseShiftLeft = '<<';
    case BitwiseShiftRight = '>>';
    case BitwiseAndNot = '&~';
    case Is = 'is';
    case IsNot = 'is not';
    case RegularExpressionLike = 'rlike';
    case NotRegularExpressionLike = 'not rlike';
    case RegularExpression = 'regexp';
    case NotRegularExpression = 'not regexp';
    case Match = '~';
    case MatchCaseInsensitive = '~*';
    case NotMatch = '!~';
    case NotMatchCaseInsensitive = '!~*';
    case SimilarTo = 'similar to';
    case NotSimilarTo = 'not similar to';
    case NotCaseInsensitiveLike = 'not ilike';
    case MatchLikeCaseInsensitive = '~~*';
    case NotMatchLikeCaseInsensitive = '!~~*';
}
