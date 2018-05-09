<?php

class profile_field_cpf extends profile_field_base {
    public function edit_field_add($mform) {
        $mform->addElement(
            'text',
            $this->inputname,
            format_string($this->field->name),
            'maxlength="11" size="11" id="profilefield_cpf" pattern="[0-9]{11}" data-tip="Informe o CPF (apenas números)" title="Apenas números"'
        );
        $mform->setType($this->inputname, PARAM_TEXT);
//	if ($this->is_required() and !has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))) {
//	       $mform->addRule($this->inputname, get_string('regexerrormessage', 'pluginname'), 'regex', '\d{11}');
               $mform->addRule($this->inputname, get_string('onlydigits', 'profilefield_cpf'), 'numeric', null, 'client');
//get_string('required'), 'nonzero', null, 'client');
//	   }
    }
    
    public function edit_validate_field($usernew) {
        $return = array();
        if (isset($usernew->{$this->inputname})) {
            if (!$this->exists($usernew->{$this->inputname}, $usernew->id)) {
                $return[$this->inputname] = get_string('cpfexists', 'profilefield_cpf');
            } else if (!$this->validatecpf($usernew->{$this->inputname})) {
                $return[$this->inputname] = get_string('invalidcpf', 'profilefield_cpf');
            }
        }
        return $return;
    }

   // Define formatação para o campo de CPF
   public function display_data() {
	if( preg_match( "/^(\d{3})(\d{3})(\d{3})(\d{2})$/", $this->data,  $matches ) )  { 
         	$result = $matches[1] . '.' .$matches[2] . '.' . $matches[3] . '-' . $matches[4];
	} else {
		$result = $this->data;
	}
	return $result;
   }


    /*
	Testa se já existe algum usuário com este mesmo número de CPF.
        Se existir, retorna falso, do contrário retorna verdadeiro.
    */
    private function exists($cpf = null, $userid = 0) {
	global $DB;

    	// Por definição, se for admin, aceita 00000000000
    	if( is_siteadmin() && $cpf == '00000000000') {
    	    return true;
    	}
    	                             	    
            // Verifica se um número foi informado.
            if (is_null($cpf)) {
                return false;
    	}

            $sql = "SELECT uid.data FROM {user_info_data} uid
    		INNER JOIN {user_info_field} uif ON uid.fieldid = uif.id
                    INNER JOIN {user} u on uid.userid = u.id
                    WHERE uif.datatype = 'cpf' AND u.deleted = 0 AND uid.data = :cpf AND uid.userid <> :userid";
            $params['cpf'] = $cpf;
            $params['userid'] = $userid;
            $dbcpf = current($DB->get_records_sql($sql, $params));

            if (!empty($dbcpf)) {
                return false;
            } else {
                return true;
            }
    }

    /*
	Verifica se um determinado valor passado corresponde a um número de CPF válido
        Retorna verdadeiro apenas se DV bater com esperado 
    */
    private function validatecpf($cpf = null) {
        // Por definição, se for admin, aceita 00000000000
	if( is_siteadmin() && $cpf == '00000000000') {
	    return true;    
	}
       
       // Verifica se um numero foi informado.
        if (is_null($cpf)) {
            return false;
        }
        if (!is_numeric($cpf)) {
            return false;
        }
        //$cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

        // Verifica se o numero de digitos informados eh igual a 11.
        if (strlen($cpf) != 11) {
            return false;
        } else if ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' ||
            $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' ||
            $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' ||
            $cpf == '99999999999') {
            return false;
        } else {
            // Calcula os digitos verificadores para verificar se o CPF eh valido.
            for ($t = 9; $t < 11; $t++) {

                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }
}
