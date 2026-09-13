<?php

namespace Studio\Seo;

use Kirby\Form\Field;

/** Native select with legacy values resolved independently for each content version. */
final class ImageModeField extends Field
{
    public function __construct(array $params)
    {
        parent::__construct('select', $params, $params['siblings'] ?? null);
    }

    public function toFormValue(): mixed
    {
        return Metadata::resolveImageMode(
            $this->value,
            $this->siblings()->get('seo_generate_image')?->toFormValue(),
            !empty($this->siblings()->get('seo_image')?->toFormValue())
        );
    }
}
