<?php

// SiteFilterType.php

namespace App\Filter;

use Symfony\Component\Form\FormBuilderInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class SiteFilterType extends CrudFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchFilter($builder, [
            'PR.name',
            "JSON_EXTRACT(PR.translations ,'$.en.name')", // TODO: pass locale
            'PR.alternateName',
        ]);

        /*
        $builder->add('country', Filters\ChoiceFilterType::class, [
            'choices' => [ 'select country' => '' ] + $options['data']['choices'],
            'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                if (empty($values['value'])) {
                    return null;
                }

                $paramName = sprintf('p_%s', str_replace('.', '_', $field));

                // expression that represent the condition
                $expression = $filterQuery->getExpr()->eq('P.countryCode', ':'.$paramName);

                // expression parameters
                $parameters = [ $paramName => $values['value'] ];

                return $filterQuery->createCondition($expression, $parameters);
            },
        ]);
        **/
    }

    public function getBlockPrefix(): string
    {
        return 'site_filter';
    }
}
