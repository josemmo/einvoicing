<?php
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Constant;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Property;
use phpDocumentor\Reflection\Php\Trait_;
use phpDocumentor\Reflection\Php\ProjectFactory;
use phpDocumentor\Reflection\Php\Project;
use phpDocumentor\Reflection\Php\Visibility;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Array_;

require __DIR__ . "/../vendor/autoload.php";

const BASE_NAMESPACE = "\\Einvoicing\\";
const SRC_DIR = __DIR__ . "/../src";
const DOCS_DIR = __DIR__ . "/../docs";
const MKDOCS_CONFIG = __DIR__ . "/../mkdocs.yml";

/**
 * Get project files
 * @return Element[string] Array of documentable classes
 */
function getProjectFiles(): array {
    $files = [];
    foreach (glob(SRC_DIR . '/{,**/}*.php', GLOB_BRACE) as $path) {
        $files[] = new LocalFile($path);
    }

    /** @var Project */
    $project = ProjectFactory::createInstance()->create('Project', $files);

    $res = [];
    foreach ($project->getFiles() as $file) {
        foreach ($file->getClasses() as $class) {
            if ($class->isAbstract()) continue;
            $res[(string) $class->getFqsen()] = $class;
        }
        foreach ($file->getTraits() as $trait) {
            $res[(string) $trait->getFqsen()] = $trait;
        }
    }
    return $res;
}


/**
 * Get class public elements
 * @param  Class_                         $class    Class instance
 * @param  string                         $type     Element type ("constants", "properties" or "methods")
 * @param  Element[string]                &$project Project files
 * @return Constant[]|Property[]|Method[]           Public elements
 */
function getPublicElements(Class_ $class, string $type, array &$project): array {
    $fn = "get" . ucfirst($type);
    $res = $class->{$fn}();

    // Elements from traits
    foreach ($class->getUsedTraits() as $traitFqsen) {
        /** @var Trait_ */
        $trait = $project[(string) $traitFqsen];
        $res = array_merge($res, $trait->{$fn}());
    }

    // Elements from parent class
    /** @var Class_|null */
    $parentClass = $project[(string) $class->getParent()] ?? null;
    if ($parentClass !== null) {
        $res = array_merge($res, getPublicElements($parentClass, $type, $project));
    }

    return array_filter($res, function($item) {
        return ($item->getVisibility() == Visibility::PUBLIC_);
    });
}


/**
 * Get class URL
 * @param  string $fqsen Class FQSEN
 * @return string        Documentation page URL
 */
function getClassUrl(string $fqsen): string {
    $parts = explode('\\', $fqsen);
    if (strpos($fqsen, BASE_NAMESPACE) === 0) {
        $slug = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', end($parts)));
        return "$slug.md";
    }
    return "https://www.php.net/manual/class." . strtolower(end($parts)) . ".php";
}


/**
 * Render class
 * @param  Class_          $class    Class instance
 * @param  Element[string] &$project Project files
 * @return string                    Markdown documentation
 */
function renderClass(Class_ $class, array &$project): string {
    $doc = "# {$class->getFqsen()}\n\n";

    // Public properties
    $properties = [];
    foreach (getPublicElements($class, 'properties', $project) as $property) {
        $properties[] = renderProperty($property, $class);
    }
    $doc .= implode("\n---\n\n", $properties);

    // Public methods
    $methods = [];
    foreach (getPublicElements($class, 'methods', $project) as $method) {
        $methods[] = renderMethod($method, $class);
    }
    $doc .= implode("\n---\n\n", $methods);

    return $doc;
}


/**
 * Render property
 * @param  Property $property Property instance
 * @param  Class_   $class    Class instance
 * @return string             Rendered method in markdown
 */
function renderProperty(Property $property, Class_ $class): string {
    $docblock = $property->getDocBlock();
    /** @var Var_ */
    $varTag = $docblock->getTagsByName('var')[0];
    $defaultValue = $property->getDefault();

    // Property summary
    $doc = "## \${$property->getName()}\n";
    $doc .= $docblock->getSummary() . "\n";

    // Signature
    $doc .= "\n```php\n";
    $doc .= "public " . ($property->isStatic() ? "static " : "");
    $doc .= renderType($varTag->getType(), $class, false) . " ";
    $doc .= "$" . $property->getName();
    if ($defaultValue !== null) {
        $doc .= " = $defaultValue";
    }
    $doc .= "\n```\n";

    return $doc;
}


/**
 * Render method
 * @param  Method $method Method instance
 * @param  Class_ $class  Class instance
 * @return string         Rendered method in markdown
 */
function renderMethod(Method $method, Class_ $class): string {
    $docblock = $method->getDocBlock();
    /** @var Param[] */
    $params = $docblock->getTagsByName('param');
    $arguments = $method->getArguments();
    /** @var Return_|null */
    $return = $docblock->getTagsByName('return')[0] ?? null;

    // Method summary
    $doc = "## `{$method->getName()}()`\n";
    $doc .= $docblock->getSummary() . "\n";

    // Signature
    $doc .= "\n```php\n";
    $doc .= "public " . ($method->isStatic() ? "static " : "") . $method->getName() . "(";
    $doc .= implode(', ', array_map(function($param, $i) use ($class, $arguments) {
        $defaultValue = $arguments[$i]->getDefault();
        return "\${$param->getVariableName()}: " . renderType($param->getType(), $class, false) .
            ($defaultValue === null ? "" : " = $defaultValue");
    }, $params, array_keys($params)));
    $doc .= ")";
    if ($return !== null) {
        $doc .= ": " . renderType($return->getType(), $class, false);
    }
    $doc .= "\n```\n";

    // Parameters
    if (!empty($params)) {
        $doc .= "\n";
        $doc .= "<h3>Parameters</h3>\n\n";
        foreach ($params as $param) {
            $doc .= "- `\${$param->getVariableName()}`: " . renderType($param->getType(), $class);
            $doc .= " — {$param->getDescription()}\n";
        }
    }

    // Return type
    if ($return !== null) {
        $doc .= "\n";
        $doc .= "<h3>Returns</h3>\n\n";
        $doc .= "- " . renderType($return->getType(), $class) . " — {$return->getDescription()}\n";
    }

    // Throws
    /** @var Throws[] */
    $throws = $docblock->getTagsByName('throws');
    if (!empty($throws)) {
        $doc .= "\n";
        $doc .= "<h3>Throws</h3>\n\n";
        foreach ($throws as $item) {
            $doc .= "- " . renderType($item->getType(), $class) . " {$item->getDescription()}\n";
        }
    }

    return $doc;
}


/**
 * Render type
 * @param  Type    $type phpDoc type instance
 * @param  Class_  $ctx  Context class
 * @param  boolean $md   Return markdown
 * @return string        Rendered type
 */
function renderType(Type $type, Class_ $ctx, bool $md=true): string {
    if ($type instanceof Compound) {
        $res = [];
        foreach ($type as $elem) {
            $res[] = renderType($elem, $ctx, $md);
        }
        return implode('|', $res);
    }

    if ($type instanceof Array_) {
        return renderType($type->getValueType(), $ctx, $md) . "[]";
    }

    if ($type instanceof Self_) {
        $fqsen = (string) $ctx->getFqsen();
        return $md ? "[`$fqsen`](" . getClassUrl($fqsen) . ")" : $fqsen;
    }

    $type = (string) $type;
    if (!$md) return $type;
    if (strpos($type, '\\') === 0) {
        return "[`$type`](" . getClassUrl($type) . ")";
    }
    return "`$type`";
}


// Generate classes documentation
$project = getProjectFiles();
$toc = [];
foreach ($project as $fqsen=>$file) {
    if ($file instanceof Class_) {
        $destPath = DOCS_DIR . "/reference/" . getClassUrl($fqsen);
        $doc = renderClass($file, $project);
        file_put_contents($destPath, $doc);
        $toc[] = substr($fqsen, strlen(BASE_NAMESPACE)) . ": reference/" . getClassUrl($fqsen);
        echo "[i] Generated documentation for $fqsen\n";
    }
}

// Update Table of Contents
$mkdocs = file_get_contents(MKDOCS_CONFIG);
$mkdocs = rtrim($mkdocs);
$mkdocs .= "\n";
$mkdocs .= "  - Reference:\n";
$mkdocs .= "    - " . implode("\n    - ", $toc) . "\n";
file_put_contents(MKDOCS_CONFIG, $mkdocs);
echo "[i] Updated Table of Contents\n";
