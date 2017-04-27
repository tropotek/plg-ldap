<?php
namespace Ldap\Controller;

use Tk\Request;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use \App\Controller\Iface;

/**
 * Class Contact
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class InstitutionSettings extends Iface
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
        parent::__construct();
        $this->setPageTitle('LDAP Plugin - Institution Settings');

        $this->institution = $this->getUser()->getInstitution();
        $this->data = \Ldap\Plugin::getInstitutionData();
    }

    /**
     * doDefault
     *
     * @param Request $request
     * @return \App\Page\Iface
     */
    public function doDefault(Request $request)
    {
        $this->form = new Form('formEdit', $request);

        $this->form->addField(new Field\Checkbox(\Ldap\Plugin::LDAP_ENABLE))->setLabel('Enable LDAP')->setNotes('Enable LDAP authentication for the institution staff and student login.');
        $this->form->addField(new Field\Input(\Ldap\Plugin::LDAP_HOST))->setLabel('LDAP Host');
        $this->form->addField(new Field\Checkbox(\Ldap\Plugin::LDAP_TLS))->setLabel('LDAP TLS');
        $this->form->addField(new Field\Input(\Ldap\Plugin::LDAP_PORT))->setLabel('LDAP Port');
        $this->form->addField(new Field\Input(\Ldap\Plugin::LDAP_BASE_DN))->setLabel('LDAP Base DN')->setNotes('Base DN query. EG: `ou=people,o=organization`.');
        $this->form->addField(new Field\Input(\Ldap\Plugin::LDAP_FILTER))->setLabel('Ldap Filter')->setNotes('Filter to locate user EG: `uid={username}`. `{username}` will be replaced with submitted username on login.');

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\LinkButton('cancel', \App\Factory::getCrumbs()->getBackUrl()));

        $this->form->load($this->data->toArray());
        $this->form->execute();

        return $this->show();
    }

    /**
     * doSubmit()
     *
     * @param Form $form
     */
    public function doSubmit($form)
    {
        $values = $form->getValues();
        $this->data->replace($values);

        if (!$values[\Ldap\Plugin::LDAP_ENABLE]) {
            if (empty($values[\Ldap\Plugin::LDAP_HOST]) || !filter_var($values[\Ldap\Plugin::LDAP_HOST], \FILTER_VALIDATE_URL)) {
                $form->addFieldError(\Ldap\Plugin::LDAP_HOST, 'Please enter a valid LDAP host');
            }
            if (empty($values[\Ldap\Plugin::LDAP_PORT]) || !is_numeric($values[\Ldap\Plugin::LDAP_PORT])) {
                $form->addFieldError(\Ldap\Plugin::LDAP_PORT, 'Please enter a valid LDAP port. [TLS: 389, SSL: 636]');
            }
            if (empty($values[\Ldap\Plugin::LDAP_BASE_DN])) {
                $form->addFieldError(\Ldap\Plugin::LDAP_BASE_DN, 'Enter a valid base DN query');
            }
            if (empty($values[\Ldap\Plugin::LDAP_FILTER])) {
                $form->addFieldError(\Ldap\Plugin::LDAP_FILTER, 'Enter a filter string to locate a user');
            }

            // TODO: Do a live connection to the LDAP
            try {
                $ldap = @ldap_connect($values[\Ldap\Plugin::LDAP_HOST], $values[\Ldap\Plugin::LDAP_PORT]);
                if ($ldap === false) {
                    $form->addError('Cannot connect to LDAP host');
                }
                if ($values[\Ldap\Plugin::LDAP_TLS])
                    @ldap_start_tls($ldap);
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
        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Factory::getCrumbs()->getBackUrl()->redirect();
            //\Tk\Uri::create('/client/plugins.html')->redirect();
        }
        \Tk\Uri::create()->redirect();
    }

    /**
     * show()
     *
     * @return \App\Page\Iface
     */
    public function show()
    {
        $template = $this->getTemplate();
        
        // Render the form
        $fren = new \Tk\Form\Renderer\Dom($this->form);
        $template->insertTemplate($this->form->getId(), $fren->show()->getTemplate());


        $formId = $this->form->getId();

        $js = <<<JS
jQuery(function($) {

  function toggleFields(checkbox) {
    var pre = checkbox.attr('name').substring(0, checkbox.attr('name').lastIndexOf('.'));
    var list = $('input[name^="'+pre+'"]').not('.ignore');
    if (!list.length) return;
    var checked = list.slice(0 ,1).get(0).checked;
    if (checked) {
      list.slice(1).removeAttr('disabled', 'disabled').removeClass('disabled');
    } else {
      list.slice(1).attr('disabled', 'disabled').addClass('disabled');
    }
  }
  $('#$formId').find('.tk-checkbox .checkbox input').change(function(e) {
    toggleFields($(this));
  }).each(function (i) {
    toggleFields($(this));
  });
   
});
JS;
        $template->appendJs($js);

        return $this->getPage()->setPageContent($template);
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div class="row" var="content">

  <div class="col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cogs fa-fw"></i> Actions
      </div>
      <div class="panel-body ">
        <div class="row">
          <div class="col-lg-12">
            <a href="javascript: window.history.back();" class="btn btn-default"><i class="fa fa-arrow-left"></i> <span>Back</span></a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cog"></i>
        LDAP Settings
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <div var="formEdit"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }
}