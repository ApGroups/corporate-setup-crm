<?php
/**
 * Validation Helper Functions
 * Corporate Setup Limited - Internal CRM
 */

class Validator {
    private $errors = [];
    private $rules = [];

    public static function make($data, $rules) {
        $validator = new self();

        foreach ($rules as $field => $fieldRules) {
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                $validator->applyRule($field, $data[$field] ?? null, $ruleName, $ruleValue, $data);
            }
        }

        return $validator;
    }

    private function applyRule($field, $value, $rule, $param = null, $data = []) {
        $fieldName = ucwords(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->errors[$field] = "{$fieldName} is required";
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "Please enter a valid email address";
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < $param) {
                    $this->errors[$field] = "{$fieldName} must be at least {$param} characters";
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > $param) {
                    $this->errors[$field] = "{$fieldName} must not exceed {$param} characters";
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->errors[$field] = "{$fieldName} must be a number";
                }
                break;

            case 'phone':
                if (!empty($value) && !preg_match('/^[\d\s\+\-\(\)]+$/', $value)) {
                    $this->errors[$field] = "Please enter a valid phone number";
                }
                break;

            case 'date':
                if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $this->errors[$field] = "Please enter a valid date";
                }
                break;

            case 'unique':
                $parts = explode(',', $param);
                $table = $parts[0];
                $column = $parts[1] ?? $field;
                $excludeId = $parts[2] ?? null;

                if (!empty($value)) {
                    $db = Database::getInstance();
                    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
                    $params = [$value];

                    if ($excludeId) {
                        $sql .= " AND id != ?";
                        $params[] = $excludeId;
                    }

                    if ($db->query($sql, $params)->fetchColumn() > 0) {
                        $this->errors[$field] = "{$fieldName} already exists";
                    }
                }
                break;

            case 'match':
                if (!empty($value) && $value !== ($data[$param] ?? '')) {
                    $this->errors[$field] = "{$fieldName} does not match";
                }
                break;

            case 'in':
                $allowed = explode(',', $param);
                if (!empty($value) && !in_array($value, $allowed)) {
                    $this->errors[$field] = "Invalid {$fieldName}";
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = "Please enter a valid URL";
                }
                break;
        }
    }

    public function errors() {
        return $this->errors;
    }

    public function firstError() {
        return reset($this->errors);
    }

    public function passes() {
        return empty($this->errors);
    }

    public function fails() {
        return !empty($this->errors);
    }
}

function validate($data, $rules) {
    return Validator::make($data, $rules);
}
