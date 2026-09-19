# Universal Code Guidelines and Code Style Rules
Version: 1.5

Audience: Humans & AI Code Assistants
Scope: Language-agnostic (C-like syntax used for examples)

---

## 1. Purpose

This document defines mandatory programming rules that must be followed by humans and AI-generated code.

Primary goals:

- Predictable structure
- Readability over brevity
- Explicit control flow
- Defensive programming
- Long-term maintainability

Automation must never sacrifice clarity.

---

## 2. Coding Standards

Follow strictly:

- For PHP, use PSR-12 rules
- Follow all rules defined in this document

Key requirements:

- Early returns (guard clauses)
- Avoid else whenever possible (else-less pattern)
- Avoid nested if/else structures
- Always use explicit braces
- Never use one-line control statements
- Separate logical blocks with blank lines
- Code must be vertically readable
- Fail fast validation
- No implicit behavior

---

## 2.1 Key Rules (Non-Negotiable)

These rules must ALWAYS be enforced:

- Use early returns (guard clauses)
- Avoid else whenever possible (else-less pattern)
- Avoid nested if/else structures
- Always use explicit braces
- Never use one-line control statements
- Separate logical blocks with blank lines
- Code must be vertically readable
- Fail fast on invalid input
- No implicit behavior

If any rule conflicts with brevity, readability wins.

---

## 3. Core Principles

### 3.1 Readability Over Cleverness

- Avoid one-liners
- Avoid implicit behavior
- Avoid compressed logic

Code must be easy to scan vertically.

---

### 3.2 Explicit Over Implicit

- All blocks must be explicit
- All scopes must be visible
- All control flow must be obvious

Hidden logic is forbidden.

---

### 3.3 Fail Fast / Fail First

- Validate input immediately
- Abort execution early
- Never propagate invalid state

---

### 3.4 Early Return / Else-less Code

- Prefer guard clauses
- Avoid else when possible
- Keep happy-path linear

---

### 3.5 Control Flow Rules (CRITICAL)

All code MUST follow this structure:

1. Validate input
2. Guard (early return)
3. Prepare data
4. Execute logic
5. Return result

---

### Example

❌ Wrong
```js
function process(data) {
    if (data) {
        const result = transform(data);

        if (result) {
            return result;
        } else {
            return null;
        }
    }
}
```

✅ Correct

```js
function process(data) {
    if (!data) {
        return null;
    }

    const result = transform(data);

    if (!result) {
        return null;
    }

    return result;
}
```

---

## 4. Control Flow Rules

### 4.1 Mandatory Braces

All control structures must use braces.

❌ Wrong

```js
if (true) return;
```

✅ Correct

```js
if (true) {
    return;
}
```

---

### 4.2 No One-Line Control Statements

Applies to:

* if / else
* for / while / foreach
* try / catch

❌ Wrong

```js
if (user) process(user);
```

✅ Correct

```js
if (user) {
    process(user);
}
```

---

### 4.3 Else-less Pattern

❌ Wrong

```js
if (!user) {
    return null;
} else {
    return user.name;
}
```

✅ Correct

```js
if (!user) {
    return null;
}

return user.name;
```

---

### 4.4 No Nested If / Else Structures

* Nested ifs are forbidden
* Nested if/else chains are forbidden
* Flatten logic using guard clauses

❌ Wrong

```js
if (a) {
    if (b) {
        run();
    }
}
```

✅ Correct

```js
if (!a) {
    return;
}

if (!b) {
    return;
}

run();
```

---

## 5. Fail Fast & Guard Clauses

### 5.1 Validate Early

❌ Wrong

```js
function save(data) {
    process(data);

    if (!data) {
        return false;
    }
}
```

✅ Correct

```js
function save(data) {
    if (!data) {
        return false;
    }

    process(data);

    return true;
}
```

---

### 5.2 Avoid Deep Nesting

* Maximum nesting depth should be minimal
* Prefer linear flow

---

## 6. Variable Declaration Rules

### 6.1 Block Scope Only

❌ Wrong

```js
var foo = 'value';
```

✅ Correct

```js
let foo = 'value';
```

---

### 6.2 No Function-Scoped Variables

❌ Wrong

```js
function example() {
    var bar = 'value';
}
```

✅ Correct

```js
function example() {
    let bar = 'value';
}
```

---

### 6.3 Avoid Reassignment When Possible

Prefer immutability.

---

## 7. Loop Rules

### 7.1 Loop Counters Must Be Block-Scoped

### 7.2 Iteration Variables Must Be Block-Scoped

---

## 8. Vertical Formatting Rules

### 8.1 Blank Line Between Logical Sections

Always separate:

* Validation
* Declarations
* Control flow
* Execution
* Return

---

### 8.2 One Concept per Block

---

### 8.3 Avoid Dense Code

---

### 8.4 Vertical Readability is Mandatory

Code must be readable from top to bottom without mental jumps.

* Each step must be visually separated
* No compressed logic
* No mixed responsibilities

---

## 9. Naming Rules

### 9.1 Names Must Describe Intent

### 9.2 Boolean Names Must Read as Questions

### 9.3 Avoid Generic Names

Forbidden:

* data
* value
* result
* temp

---

## 10. Functions

### 10.1 Single Responsibility

### 10.2 Prefer Small, Composable Functions

### 10.3 Limit Function Size

---

## 11. Comments

### 11.1 Code Explains How, Comments Explain Why

### 11.2 Do Not Comment Obvious Code

### 11.3 Remove Dead Code

---

## 12. Error Handling

### 12.1 Never Ignore Errors

### 12.2 Fail Loud, Not Silent

---

## 13. Constants & Magic Values

### 13.1 No Magic Numbers

---

## 14. PHP-Specific Rules

### 14.1 PSR Compliance

* PSR-1 and PSR-12 are mandatory
* 4 spaces indentation
* UTF-8 without BOM

---

### 14.2 Naming

* camelCase for variables/methods
* PascalCase for classes

---

### 14.3 Strict Types

```php
declare(strict_types=1);
```

---

### 14.4 Type Safety

* Always type inputs and outputs
* Avoid mixed

---

### 14.5 Input Validation (CRITICAL)

Never trust external input.

* Always validate and sanitize
* Use filter_var / filter_input

---

### 14.6 Error Handling

* Never use @
* Use exceptions properly
* Never swallow exceptions

---

### 14.7 Database

* Prefer ORM
* Otherwise use PDO
* Always use prepared statements

---

### 14.8 Dependency Management

* Prefer DI
* Avoid instantiating inside methods

---

### 14.9 Dependency Inversion

* Use when it adds value
* Avoid overengineering

---

### 14.10 Modern PHP

Use when it improves clarity:

* match
* constructor promotion
* nullsafe
* readonly

---

### 14.11 Comments & Docs

* Avoid noise
* Use PHPDoc when needed

---

### 14.12 Architecture

* Follow SOLID
* Separate domain from infrastructure

---

### 14.13 Testing

* Prefer unit tests
* Use mocks/stubs

---

### 14.14 Property Visibility

* Prefer `protected` over `private` for class properties.
* Use `private` only when strictly necessary for encapsulation that must not be overridden.

---

### 14.15 Static References

* Always use `static::` instead of `self::` for internal static references.
* Use `self::` only when the reference strictly REQUIRES the current class definition (preventing late static binding), which is rare.
* This ensures better support for inheritance and late static binding.

---

### 14.16 Return Type Hints

* When a method returns an instance of the class (or a child class), use `static` as the return type hint instead of `self`.
* This applies to PHP 8.0+ environments.
* Example: `public function getInstance(): static`

---

## 15. Forbidden Patterns

* One-line control flow
* Nested if/else
* Silent errors
* Magic numbers
* Dead code

---

## 16. AI Enforcement Rules

The AI must:

1. Follow all rules
2. Prefer clarity over brevity
3. Use guard clauses
4. Avoid nesting
5. Use explicit structure

---

## 17. Guiding Mental Model

Code must read:

1. Validate
2. Guard
3. Prepare
4. Execute
5. Return

---

End of document.
