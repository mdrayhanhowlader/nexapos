<?php

class Validator
{
    private array $errors  = [];
    private array $data    = [];
    private array $rules   = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        if (str_starts_with($rule, 'required')) {
            if ($value === null || $value === '' || $value === []) {
                $this->errors[$field][] = "{$label} is required";
            }
            return;
        }

        if ($value === null || $value === '') return;

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "{$label} must be a valid email";
            }
        } elseif ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->errors[$field][] = "{$label} must be a number";
            }
        } elseif ($rule === 'integer') {
            if (!filter_var($value, FILTER_VALIDATE_INT)) {
                $this->errors[$field][] = "{$label} must be an integer";
            }
        } elseif (str_starts_with($rule, 'min:')) {
            $min = (float) substr($rule, 4);
            if (is_numeric($value) && (float)$value < $min) {
                $this->errors[$field][] = "{$label} must be at least {$min}";
            } elseif (is_string($value) && strlen($value) < $min) {
                $this->errors[$field][] = "{$label} must be at least {$min} characters";
            }
        } elseif (str_starts_with($rule, 'max:')) {
            $max = (float) substr($rule, 4);
            if (is_numeric($value) && (float)$value > $max) {
                $this->errors[$field][] = "{$label} must not exceed {$max}";
            } elseif (is_string($value) && strlen($value) > $max) {
                $this->errors[$field][] = "{$label} must not exceed {$max} characters";
            }
        } elseif (str_starts_with($rule, 'in:')) {
            $options = explode(',', substr($rule, 3));
            if (!in_array($value, $options)) {
                $this->errors[$field][] = "{$label} must be one of: " . implode(', ', $options);
            }
        } elseif ($rule === 'url') {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = "{$label} must be a valid URL";
            }
        } elseif ($rule === 'phone') {
            if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $value)) {
                $this->errors[$field][] = "{$label} must be a valid phone number";
            }
        } elseif ($rule === 'alpha_num') {
            if (!ctype_alnum($value)) {
                $this->errors[$field][] = "{$label} must contain only letters and numbers";
            }
        }
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? '';
        }
        return '';
    }

    public function validated(): array
    {
        return array_intersect_key($this->data, $this->rules);
    }
}
