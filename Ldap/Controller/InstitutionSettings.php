<?php
namespace Ldap\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Ldap\Plugin;

/**
 * Class Contact
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class InstitutionSettings extends \Bs\Controller\AdminIface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \App\Db\Institution
     */
    protected $institution = null;

    /**
     * @var \Tk\Db\Data|null
     */
    protected $data = null;


    /**
     *
     */
    public function __construct()
    {
        $this->setPageTitle('LDAP Plugin - Institution Settings');
    }

    /**
     * doDefault
     *
     * @param Request $request
     * @throws Form\Exception
     * @throws \Exception
     * @throws \Tk\Db\Exception
     */
    public function doDefault(Request $request)
    {

        $this->institution = \App\Db\InstitutionMap::create()->find($request->get('zoneId'));
        $this->data = Plugin::getInstitutionData($this->institution);

        $this->form = \Uni\Config::createForm('formEdit');
        $this->form->setRenderer(\Uni\Config::createFormRenderer($this->form));

        $this->form->addField(new Field\Checkbox(Plugin::LDAP_ENABLE))->addCss('tk-input-toggle')
            ->setLabel('Enable LDAP')->setNotes('Enable LDAP authentication for the institution staff and student login.');
        $this->form->addField(new Field\Input(Plugin::LDAP_HOST))->setLabel('LDAP Host')
            ->setNotes('EG: ldaps://server.unimelb.edu.au');
        $this->form->addField(new Field\Input(Plugin::LDAP_PORT))->setLabel('LDAP Port')->setValue('636');
        $this->form->addField(new Field\Input(Plugin::LDAP_BASE_DN))->setLabel('LDAP Base DN')
            ->setNotes('Base DN query. EG: `uid=%s,ou=people,o=organization`.');

        $this->form->addField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', $this->getConfig()->getSession()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

    }

    /**
     * doSubmit()
     *
     * @param Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Tk\Db\Exception
     */
    public function doSubmit($form, $event)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        if ($values[Plugin::LDAP_ENABLE]) {
            if (empty($values[Plugin::LDAP_HOST]) || !filter_var($values[Plugin::LDAP_HOST], \FILTER_VALIDATE_URL)) {
                $form->addFieldError(Plugin::LDAP_HOST, 'Please enter a valid LDAP host');
            }
            if (empty($values[Plugin::LDAP_PORT]) || !is_numeric($values[Plugin::LDAP_PORT])) {
                $form->addFieldError(Plugin::LDAP_PORT, 'Please enter a valid LDAP port. [TLS: 389, SSL: 636]');
            }
            if (empty($values[Plugin::LDAP_BASE_DN])) {
                $form->addFieldError(Plugin::LDAP_BASE_DN, 'Enter a valid base DN query');
            }

            try {
                $ldap = @ldap_connect($values[Plugin::LDAP_HOST], $values[Plugin::LDAP_PORT]);
                if ($ldap === false) {
                    $form->addError('Cannot connect to LDAP host');
                }
                if (!@ldap_bind($ldap)) {   // Still not error checking the connection correctly, but will do for now.
                    $form->addError('Failed to bind to LDAP host, check your settings or contact your LDAP administrator.');
                }
            } catch (\Exception $e) {
                $form->addError($e->getMessage());
            }
        }

        if ($this->form->hasErrors()) {
            return;
        }
        
        $this->data->save();

        \Tk\Alert::addSuccess('LDAP Settings saved.');
        $event->setRedirect($this->getConfig()->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create());
        }
    }

    /**
     * show()
     *
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();
        
        // Render the form
        $template->insertTemplate($this->form->getId(), $this->form->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div var="content">
  
  <div class="panel panel-default">
    <div class="panel-heading"><i class="fa fa-cog"></i> LDAP Settings</div>
    <div class="panel-body">
      <div var="formEdit"></div>
    </div>
  </div>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}