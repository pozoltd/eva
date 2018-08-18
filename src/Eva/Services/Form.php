<?php
namespace Eva\Services;

use Pz\Common\Utils;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Form implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['form'] = $this;
    }

    public function boot(Application $app)
    {
        $this->app = $app;
    }


    public function getForm($code, $options = array())
    {
        $dao = isset($options['dao']) && $options['dao'] ? $options['dao'] : null;

        $formDescriptorClass = isset($this->app['formDescriptorClass']) ? $this->app['formDescriptorClass'] : '\\Eva\\ORMs\\FormDescriptor';
        $formDescriptor = $formDescriptorClass::getORMByField($this->app['zdb'], 'code', $code);
        if (is_null($formDescriptor)) {
            $this->app->abort(404);
        }
        $formDescriptor->sent = false;

        $formBuilderClass = isset($this->app['formBuilderClass']) ? $this->app['formBuilderClass'] : '\\Eva\\Forms\\FormBuilder';
        $formBuilder = new $formBuilderClass($formDescriptor, $this->app, array());
        $formDescriptor->form = $this->app['form.factory']->create(
            $formBuilder, $dao
        );

        $request = $this->app['request'];
        if ('POST' == $request->getMethod() && isset($_POST['form_' . $formDescriptor->code])) {// we need to make sure we have some sort of token before handling a post, look for csrf

            $formDescriptor->form->bind($request);

            if ($formDescriptor->form->isValid()) {
                $data = (array)$formDescriptor->form->getData();
                $result = array();
                foreach (json_decode($formDescriptor->fields) as $field) {
                    if ($field->widget == 'submit') {
                        continue;
                    }
                    $result[] = array($field->label, $data[$field->id], $field->widget);
                    $formDescriptor->thankyouMessage = str_replace("{{$field->id}}", $data[$field->id], $formDescriptor->thankyouMessage);
                }
                $this->beforeSend($formDescriptor, $result, $data, $dao);

                if ($dao) {
                    $dao->save();
                }

                if ($formDescriptor->recipients) {
                    $code = uniqid();
                    $formSubmissionClass = isset($this->app['formSubmissionClass']) ? $this->app['formSubmissionClass'] : '\\Eva\\ORMs\\FormSubmission';
                    $submission = new $formSubmissionClass($this->app['zdb']);
                    $submission->title = '#' . $code . ' ' . (isset($data['email']) ? $data['email'] : '');
                    $submission->uniqueId = $code;
                    $submission->date = date('Y-m-d H:i:s');
                    $submission->from = $formDescriptor->from;
                    $submission->recipients = $formDescriptor->recipients;
                    $submission->content = json_encode($result);
                    $submission->emailStatus = 0;
                    $submission->save();


                    $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/../../../views/');
                    $messageBody = $this->app['twig']->render('email.twig', array(
                        'submission' => $submission,
                    ));

                    $message = \Swift_Message::newInstance()
                        ->setSubject(CLIENT . " {$formDescriptor->title}#" . $submission->uniqueId)
                        ->setFrom(array($formDescriptor->from))
                        ->setTo(array_filter(array_map('trim', explode(',', $formDescriptor->recipients))))
                        ->setBcc(array(EMAIL_BCC))
                        ->setBody(
                            $messageBody, 'text/html'
                        );
                    if (isset($data['email']) && $data['email'] && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $message->setReplyTo(array($data['email']));
                    }
                    $formDescriptor->sent = $this->app['mailer']->send($message);

                    $submission->emailStatus = $formDescriptor->sent ? 1 : 2;
                    $submission->emailRequest = $messageBody;
                    $submission->emailResponse = $formDescriptor->sent;
                    $submission->save();
                } else {
                    $formDescriptor->sent = 1;
                }

                $this->afterSend($formDescriptor, $result, $data, $dao);
            }
        }

        $formDescriptor->form = $formDescriptor->form->createView();
        return $formDescriptor;

    }

    public function beforeSend($formDescriptor, &$result, $data, $dao)
    {

    }

    public function afterSend($formDescriptor, &$result, $data, $dao)
    {

    }
}
