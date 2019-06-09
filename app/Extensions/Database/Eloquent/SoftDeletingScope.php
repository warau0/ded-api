<?php

namespace App\Extensions\Database\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletingScope as BaseSoftDeletingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SoftDeletingScope extends BaseSoftDeletingScope
{
	/**
	 * Apply the scope to a given Eloquent query builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return void
	 */
	public function apply(Builder $builder, Model $model)
	{
		$builder->where($model->getQualifiedDeletedAtColumn(), '=', config('constants.NOT_DELETED'));
	}

	/**
	 * Add the restore extension to the builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	protected function addRestore(Builder $builder)
	{
		$builder->macro('restore', function (Builder $builder) {
			$builder->withTrashed();

			return $builder->update([$builder->getModel()->getDeletedAtColumn() => config('constants.NOT_DELETED')]);
		});
	}

	/**
	 * Add the without-trashed extension to the builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	protected function addWithoutTrashed(Builder $builder)
	{
		$builder->macro('withoutTrashed', function (Builder $builder) {
			$model = $builder->getModel();

			$builder->withoutGlobalScope($this)->where(
				$model->getQualifiedDeletedAtColumn(),
				'=',
				config('constants.NOT_DELETED')
			);

			return $builder;
		});
	}

	/**
	 * Add the only-trashed extension to the builder.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	protected function addOnlyTrashed(Builder $builder)
	{
		$builder->macro('onlyTrashed', function (Builder $builder) {
			$model = $builder->getModel();

			$builder->withoutGlobalScope($this)->where(
				$model->getQualifiedDeletedAtColumn(),
				'=',
				config('constants.NOT_DELETED')
			);

			return $builder;
		});
	}
}
