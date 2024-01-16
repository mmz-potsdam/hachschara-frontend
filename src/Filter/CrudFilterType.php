<?php
// CrudFilterType.php
namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityManagerInterface;;

use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type as Filters;

class CrudFilterType
extends AbstractType
{
    protected $useIcuRegexp = true;

    public function __construct(EntityManagerInterface $em)
    {

        $params = $em->getConnection()->getParams();
        if (array_key_exists('serverVersion', $params) && version_compare($params['serverVersion'], '8.0.4', '<')) {
            $this->useIcuRegexp = false;
        }
    }

    protected function addSearchFilter(FormBuilderInterface $builder, array $searchFields, $useFulltext = false)
    {
        $builder->add('search', Filters\TextFilterType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'Search',
                'class' => 'text-field-class w-input search-input input-text-search',
            ],
            'apply_filter' => function (QueryInterface $filterQuery, $field, $values) use ($searchFields, $useFulltext)
            {
                if (empty($values['value'])) {
                    return null;
                }

                if ($useFulltext) {
                    // on Innodb, this needs a FULLTEXT index matching the column list
                    $fulltextCondition = \App\Utils\MysqlFulltextSimpleParser::parseFulltextBoolean($values['value'], true);

                    // requires "beberlei/DoctrineExtensions"
                    $expression = sprintf("MATCH (%s) AGAINST ('%s' BOOLEAN) = TRUE",
                                          implode(', ', $searchFields),
                                          $fulltextCondition);
                    $parameters = [];
                }
                else {
                    $conditions = $parameters = [];

                    $orWords = explode(";", $values['value'] );

                    $orExpressions = [];
                    // build a matching REGEX
                    $counter = 0;

                    $wordBegin = $this->useIcuRegexp
                        ? '\\b' : '[[:<:]]';

                    foreach ($orWords as $currValues) {
                        $words = preg_split('/\,?\s+/', trim($currValues));
                        if (count($words) > 0) {
                            $andParts = [];

                            for ($i = 0; $i < count($words); $i++) {
                                if (empty($words[$i])) {
                                    continue;
                                }

                                $bindKey = 'regexp' . $counter;
                                $parameters[$bindKey] =  $wordBegin . $words[$i];

                                $orParts = [];
                                for ($j = 0; $j < count($searchFields); $j++) {
                                    // see https://stackoverflow.com/a/29034983/2114681
                                    // TODO: use $parameters instead of addslashes
                                    $orParts[] = sprintf("REGEXP(%s, :%s) = true",
                                                         $searchFields[$j], $bindKey);
                                }

                                $andParts[] = '(' . implode(' OR ', $orParts) . ')';

                                $counter++;
                            }

                            if (count($andParts) > 0) {
                                $conditions[] = implode(' AND ', $andParts);
                            }
                        }

                        if (empty($conditions)) {
                            return null;
                        }

                        $expression = join(' AND ', $conditions);
                        array_push($orExpressions, "(         " . $expression . "         )");
                    }

                    $expression = join(' OR ', $orExpressions);
                }

                return $filterQuery->createCondition($expression, $parameters);
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection'   => false,
            'validation_groups' => ['filtering'] // avoid NotBlank() constraint-related message
        ]);
    }
}
