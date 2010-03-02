<?php
/**
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Lesser General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author Daniel Bernardi <taniele.billini@gmail.com>
* @link http://github.com/dbernardi/Validation
*/
	//-------------------------------------------------------------------------
	// filename: validation.class.php
	// version: 1.0.5
	//
	// SYNTAX
	//
	// array Validation::validate(array $field_data, array $fields)
	//
	//
	// PARAMETERS
	//
	// $field_data is an array which contains the data to be checked
	// (most likely you will assign $_POST).
	// 
	// $fields is an array of objects which contains:
	//
	// $fields->field			$_REQUEST["name"] of field to validate
	// $fields->value			value of field to validate
	// $fields->check			the check to perform on that field
	// $fields->check_value 	for binary check operators, such as lower_than,
	//							date_lower_than or password:
	//							the value to compare against $fields->value
	//
	// The list of controls is listed in the Validation::check_types array.
	//
	// Multiple entries with the same $_REQUEST name can be added to perform
	// more than one check at time for the same field.
	//
	//
	// CHANGELOG
	//
	// 20071127 gag-daniele: updated comments
	// 20071030 gag-daniele: added other checks
	// 20071029 gag-daniele: bugfix on check_password
	// 20071026 gag-irene: bugfixes
	// 20071024 gag-daniele: bugfixes; validate optional fields only if not empty   
	// 20071023 gag-daniele: created
	//-------------------------------------------------------------------------
	
	class ValidationPlugin extends FangoPlugin {
		
		// Types of checks. To add a new check just add an entry like:
		//
		// "check_type" => array("function_to_call", [true | false])
		//
		// where true or false means if the function should read a value
		// from the check_value property.
	
		var $check_types = array(
			//	name						 method to call			needs a value to compare against?
				"required" 			=> array("check_required", 		false),
				"password" 			=> array("check_equal", 		true),
				"equal_to" 			=> array("check_equal", 		true),

				"date" 				=> array("check_valid_date", 	false),
				"date_past" 		=> array("check_date_past",		false),
				"date_future" 		=> array("check_date_future", 	false),
				"date_lower_than" 	=> array("check_date_lt", 		true),
				"date_greater_than" => array("check_date_gt", 		true),

				"lower_than" 		=> array("check_lt", 			true),
				"greater_than" 		=> array("check_gt", 			true),
				"alpha" 			=> array("check_alpha", 		false),
				"numeric" 			=> array("check_digit", 		false),

				"valid_mail"		=> array("check_valid_mail", 	false),
				"fiscal_code"		=> array("check_fiscal_code", 	false),
				"vat_number"		=> array("check_vat_number",	false)				
		);

		function check_gt($value, $check_value) { return ($value > $check_value); }
		function check_lt($value, $check_value) { return ($value < $check_value); }
		
		function check_alpha($value) { return preg_match('/^[a-zA-Z]+$/', $value); }
		function check_digit($value) { return is_numeric($value); }
		
		function check_vat_number($value) {
			// 20071030 gag-daniele:
			// I took this check from http://www.icosaedro.it/cf-pi/pi-php.txt
			if(!is_numeric($value)) return false;
			if(strlen($value) != 11	|| !ereg("^[0-9]+$", $value)) return false;
			else {
				$s = 0;
				
				for($i = 0; $i <= 9; $i += 2)
					$s += ord($value[$i]) - ord('0');
				
				for($i = 1; $i <= 9; $i += 2 )
				{
					$c = 2*( ord($value[$i]) - ord('0') );
					if( $c > 9 )  $c = $c - 9;
					$s += $c;
				} // for
				
				if((10 - $s % 10 ) % 10 != ord($value[10]) - ord('0'))
					return false;
			} // if
			return true;
		}
		
		function check_fiscal_code($value) {
			$regex_verify = "/[a-zA-Z]{6}[0-9]{2}[a-zA-Z][0-9]{2}[a-zA-Z][0-9]{3}[a-zA-Z]/i";
			if(preg_match($regex_verify, $value) == 0) return false;
				else return true;
		}
		
		function check_date_lt($value, $check_value) {
			// This check requires a date in the format dd/mm/yyyy
			if(!$this->check_valid_date($value)) return false;
			
			list($day, $month, $year) = explode("/", $value);
			list($check_day, $check_month, $check_year) = explode("/", $check_value);
			
			if(mktime(0, 0, 0, $month, $day, $year) < mktime(0, 0, 0, $check_month, $check_day, $check_year))
				return true;
			else return false;
		}

		function check_date_gt($value, $check_value) {
			// This check requires a date in the format dd/mm/yyyy
			if(!$this->check_valid_date($value)) return false;
			
			list($day, $month, $year) = explode("/", $value);
			list($check_day, $check_month, $check_year) = explode("/", $check_value);
			
			if(mktime(0, 0, 0, $month, $day, $year) > mktime(0, 0, 0, $check_month, $check_day, $check_year))
				return true;
			else return false;
		}

		
		function check_date_future($value) {
			return $this->check_date_gt($value, date("d/m/Y"));
		}
		

		function check_date_past($value) {
			return $this->check_date_lt($value, date("d/m/Y"));
		}


		function check_required($value) {
			if(!empty($value)) return true;
			else return false;
		} // function check_required



		function check_valid_date($value) {
			// This check requires a date in the format dd/mm/yyyy
			if(empty($value)) $value = "0/0/0";
			
			list($day, $month, $year) = explode("/", $value);
			return checkdate($month, $day, $year);
		} // function valid_date;


		function check_equal($value, $check_value = "") {
			// This method is case sensitive
			if($this->check_digit($value) && $this->check_digit($check_value))
				return ($value == $check_value);
			else
				return (strcmp($value, $check_value) == 0 ? true : false);

		} // function check_password
		
		function check_valid_mail($value) {
			if(!eregi("^[a-z0-9][_\.a-z0-9-]+@([a-z0-9][0-9a-z-]+\.)+([a-z]{2,4})", $value))
				return false;
			else return true;	
		} // function check_valid_mail
		
		function parse_fields($field_data, $params) {
			$i = 0;
			foreach($field_data as $key=>$value) {
				
				// Excludes from validation empty fields if not required 
				if((empty($value) && isset($params[$key]["required"]))
				|| (!empty($value) && isset($key, $params[$key]))) {
					if(is_array($params[$key])) {
					
						foreach($params[$key] as $param_key => $param_value) {
							$data[$i]->field = $key;
							$data[$i]->value = $value;
							$data[$i]->check = $param_key;
					
							if($this->check_types[$param_key][1]) {
									$data[$i]->check_value = $param_value;
							}
							$i++;
						} // foreach
					} // if
				} // if
			} // foreach
			return $data;	
		} // function parse_fields
		
		// Main controller
		function validate($data, $params) {
			
			$fields = $this->parse_fields($data, $params);

			$v = array();
			$i = 0;
			$ret = true;

			foreach($fields as $field) {

				// Check if the parameters are valid
				if(empty($field->field) || empty($field->check)) {
					continue;
				}
			
				if(in_array($field->check, array_keys($this->check_types))) {
					// Check if the entry needs to read from check_value

					if($this->check_types[$field->check][1]	&& (isset($field->check_value) && !empty($field->check_value))) {
						if(method_exists($this, $this->check_types[$field->check][0])) {
							$ret = call_user_func(array($this, $this->check_types[$field->check][0]), $field->value, $field->check_value);
							
						} // if
					} // if

					else {
						if(method_exists($this, $this->check_types[$field->check][0])) {
							$ret = call_user_func(array($this, $this->check_types[$field->check][0]), $field->value);

						} // if

					} // else					

					if($ret == false) {
						$v[$i]->field = $field->field;
						$v[$i]->result = $ret;
					} // if

				} // if
				$i++;
			} // foreach

			return $v;
		} // function validate
		
		
	} // class Validation
	
?>
