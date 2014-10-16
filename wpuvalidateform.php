<?php

/*
Plugin Name: WPU Validate form
Plugin URI: https://github.com/WordPressUtilities/wpuvalidateform
Description: Form validation
Version: 0.3.4
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUValidateForm
{
    private $messages;

    public function validate_values_from($model_form, $source = false) {
        $this->has_errors = false;
        $this->messages = array();
        $values = array();

        // Default source : POST
        if ($source === false) {
            $source = $_POST;
        }

        foreach ($model_form as $key => $tests) {
            $val = '';
            $val_has_error = false;

            if (!isset($tests['required']) || !is_bool($tests['required'])) {
                $tests['required'] = false;
            }

            // Check posted field
            if (!array_key_exists($key, $source) && $tests['required']) {
                $this->messages[] = array(
                    'type' => 'error',
                    'content' => 'Il manque le champ "' . $key . '"'
                );
                $val_has_error = 1;
                $this->has_errors = 1;
            } else {
                $val = trim($source[$key]);
            }

            if (!$val_has_error) {
                $val_has_error = $this->apply_tests($tests, $val, $key);
            }

            // Add to values if no error
            if (!$val_has_error) {
                $values[$key] = strip_tags($val);
            }
        }

        return array(
            'has_errors' => $this->has_errors,
            'values' => $values,
            'messages' => $this->messages
        );
    }

    private function apply_tests($tests, $val, $key) {

        $val_has_error = false;

        // Tests
        foreach ($tests as $test_id => $test_val) {
            $test_valid = true;

            // Dont continue if val has an error
            if ($val_has_error) {
                break;
            }

            $label_field = $key;
            if (isset($tests['label'])) {
                $label_field = $tests['label'];
            }

            switch ($test_id) {
                case 'required':
                    if ($test_val !== false && empty($val)) {
                        $test_valid = 'Le champ "' . $label_field . '" est requis';
                    }
                    break;

                case 'isnumeric':
                    if ($test_val !== false && !empty($val) && !is_numeric($val)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être un nombre';
                    }
                    break;

                case 'isdate':
                    if ($test_val !== false && !empty($val) && !$this->isValidDate($val)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être une date au format JJ/MM/AAAA';
                    }
                    break;

                case 'isemail':
                    if ($test_val !== false && !empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être un email';
                    }
                    break;

                case 'isurl':
                    if ($test_val !== false && !empty($val) && !filter_var($val, FILTER_VALIDATE_URL)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être une URL';
                    }
                    break;

                case 'isfrssn':
                    $pattern = '/^([12])([0-9]{2}(0[1-9]|1[0-2]))(2[AB]|[0-9]{2})([0-9]{6})([0-9]{2})?$/x';
                    if ($test_val !== false && !empty($val) && !preg_match($pattern, $val)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être un numéro de sécurité social valide';
                    }
                    break;

                case 'iszipcode':
                    if ($test_val !== false && !empty($val) && !preg_match('/^[0-9]{5}$/', $val)) {
                        $test_valid = 'Le champ "' . $label_field . '" doit être un code postal';
                    }
                    break;

                case 'value_in':
                    if (is_array($test_val) && !array_key_exists($val, $test_val)) {
                        $test_valid = 'La valeur de "' . $key . '" est invalide';
                    }
                    break;
            }

            if ($test_valid !== true) {
                $this->messages[] = array(
                    'type' => 'error',
                    'content' => $test_valid
                );
                $val_has_error = 1;
                $this->has_errors = 1;
            }
        }

        return $val_has_error;
    }

    public function isValidDate($date) {
        preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches);
        return checkdate(intval($matches[2]) , intval($matches[1]) , intval($matches[3]));
    }
}
