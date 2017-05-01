<?php
namespace Eva\Forms;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Model extends AbstractType
{

    public function getName()
    {
        return 'form';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $defaultSortByOptions = isset($options['defaultSortByOptions']) ? $options['defaultSortByOptions'] : array();

        $builder->add('title', 'text', array(
            'label' => 'Name:',
            'constraints' => array(
                new Assert\NotBlank()
            )
        ))->add('className', 'text', array(
            'label' => 'Class name:',
            'constraints' => array(
                new Assert\NotBlank()
            )
        ))->add('namespace', 'text', array(
            'label' => 'Namespace:',
            'constraints' => array(
                new Assert\NotBlank()
            )
        ))->add('dataTable', 'text', array(
            'label' => 'Database table:',
            'constraints' => array(
                new Assert\NotBlank()
            )
        ))->add('modelType', 'choice', array(
            'label' => 'Model type:',
            'expanded' => true,
            'choices' => array(
                0 => 'Customised',
                1 => 'Built in',
            )
        ))->add('dataType', 'choice', array(
            'label' => 'Data type:',
            'expanded' => true,
            'choices' => array(
                0 => 'Database',
                1 => 'Admin',
                2 => 'None',
            )
        ))->add('listType', 'choice', array(
            'label' => 'Listing type:',
            'expanded' => true,
            'choices' => array(
                1 => 'Drag & Drop',
                2 => 'Nested tree',
                0 => 'Pagination',
            )
        ))->add('numberPerPage', 'text', array(
            'label' => 'Page size:',
        ))->add('defaultSortBy', 'choice', array(
            'label' => 'Sort:',
            'choices' => $defaultSortByOptions,
        ))->add('defaultOrder', 'choice', array(
            'label' => 'Order:',
            'expanded' => true,
            'choices' => array(
                0 => 'ASC',
                1 => 'DESC'
            )
        ))->add('columnsJson', 'hidden');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
            'defaultSortByOptions' => array(),
        ));
    }
}
