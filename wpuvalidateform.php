<?php

/*
Plugin Name: WPU Validate form
Plugin URI: https://github.com/WordPressUtilities/wpuvalidateform
Description: Form validation
Version: 0.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUValidateForm
{

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

            // Check posted field
            if (!array_key_exists($key, $source)) {
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
                $values[$key] = $val;
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

            switch ($test_id) {
                case 'required':
                    if ($test_val !== false && empty($val)) {
                        $test_valid = 'Le champ "' . $key . '" est requis';
                    }
                    break;

                case 'isnumeric':
                    if ($test_val !== false && !empty($val) && !is_numeric($val)) {
                        $test_valid = 'Le champ "' . $key . '" devrait être un nombre';
                    }
                    break;

                case 'isdate':
                    if ($test_val !== false && !empty($val) && !$this->isValidDate($val)) {
                        $test_valid = 'Le champ "' . $key . '" devrait être une date au format AAAA-MM-JJ';
                    }
                    break;

                case 'isemail':
                    if ($test_val !== false && !empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $test_valid = 'Le champ "' . $key . '" devrait être un email';
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
        preg_match('/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/', $date, $matches);
        return checkdate(intval($matches[2]) , intval($matches[3]) , intval($matches[1]));
    }
}
