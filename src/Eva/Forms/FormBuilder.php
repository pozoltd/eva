<?php

namespace Eva\Forms;

use Eva\Forms\Types\Robot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Validator\Constraints as Assert;


class FormBuilder extends AbstractType
{

    protected $app;
    protected $formDescriptor;
    protected $options;

    public function __construct($formDescriptor, $app, $options = array())
    {
        $this->formDescriptor = $formDescriptor;
        $this->app = $app;
        $this->options = $options;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields = json_decode($this->formDescriptor->fields);
        foreach ($fields as $key => $field) {
            if (strpos($field->widget, '\\') !== false) {
                $field->widget = new $field->widget();
            }
            $builder->add($field->id, $field->widget, $this->getOptionsForField($field));
        }

        if ($this->formDescriptor->antiSpam) {
            $builder->add('antispam', new Robot(), array(
                "mapped" => false,
                'label' => '',
                'constraints' => array(
                    new \Eva\Forms\Constraints\Robot(),
                )
            ));
        }
    }

    public function getName()
    {
        return 'form_' . $this->formDescriptor->code;
    }

    public function getOptionsForField($field)
    {
        $options = array(
            'label' => $field->label,
            'attr' => array(
                'placeholder' => preg_replace("/[^a-zA-Z0-9\ ]+/", "", $field->label),
            ),
        );

        switch ($field->widget) {
            case 'choice':
                $options['choices'] = $this->getChoicesForField($field);
                $options['multiple'] = false;
                $options['expanded'] = false;
                $options['empty_data'] = null;
                $options['required'] = false;
                $options['placeholder'] = 'Choose...';
                break;
            case 'repeated':
                $options['type'] = 'password';
                $options['invalid_message'] = 'The password fields must match.';
                $options['options'] = array('attr' => array('class' => 'password-field'));
                $options['required'] = true;
                $options['first_options'] = array('label' => 'Password (8 characters or more):', 'attr' => array('placeholder' => 'Enter Password'));
                $options['second_options'] = array('label' => 'Repeat Password:', 'attr' => array('placeholder' => 'Confirm Password'));
                break;
        }

        $constraints = $this->getValidationForField($field);
        if (count($constraints) > 0) {
            $options['constraints'] = $constraints;
        }

        return $options;
    }


    public function getChoicesForField($field)
    {
        if (isset($field->sql)) {
            $conn = $this->app['em']->getConnection();
            $stmt = $conn->executeQuery($field->sql);
            $stmt->execute();
            $choices = array();
            foreach ($stmt->fetchAll() as $key => $val) {
                $choices[$val['key']] = $val['value'];
            }
            return $choices;
        }
        return array();
    }


    public function getValidationForField($field)
    {

        $validations = array();

        if ($field->widget == 'email') {
            $validations[] = new Assert\Email();
        }

        if ($field->widget == 'repeated') {
            $validations[] = new Assert\Length(array(
                'min' => 8,
                'max' => 32,
            ));
        }

        if (isset($field->required) && $field->required) {
            $validations[] = new Assert\NotBlank();
        }

        return $validations;
    }

}
