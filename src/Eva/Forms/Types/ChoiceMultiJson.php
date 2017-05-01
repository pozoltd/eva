<?php
namespace Eva\Forms\Types;

use Pz\Common\Utils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceMultiJson extends AbstractType
{

    public function getName()
    {
        return 'choice_multi_json';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['choices'] = array();
        foreach ($options['choices'] as $idx => $itm) {
            $view->vars['choices'][] = array(
                'value' => $idx,
                'label' => $itm,
            );
        }
    }


    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false,
            'choices' => array(),
            'placeholder' => "Choose options...",
        ));
    }

}

