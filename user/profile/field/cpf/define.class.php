<?php

class profile_define_cpf extends profile_define_base {
    public function define_form_specific($form) {
        // Default data.
        $form->addElement('text', 'defaultdata', 'Informe o CPF');
		$form->setType('defaultdata', PARAM_TEXT);
        $form->setDefault('defaultdata', '(somente dígitos)')
    }

   function define_validate_specific($data) {
     $errors = array();
     // Make sure defaultdata is not false
     if ($data->defaultdata == false) {
       $errors['cpf'] = 'Obrigatório'; //get_string('noprovided', 'profilefield_cpf');
     }
     return $errors;
   }
}
