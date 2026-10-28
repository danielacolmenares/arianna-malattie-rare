<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['query', 'normalized_query', 'result_count'])]
class SearchEvent extends Model
{
}
