<?php
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\File\LocalFile;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Method;
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
 * Get public methods
 * @param  Class_          $class   Class instance
 * @param  Element[string] $project Project files
 * @return Method[]                 Public methods
 */
function getPublicMethods(Class_ $class, array $project): array {
    $methods = $class->getMethods();

    // Methods from traits
    foreach ($class->getUsedTraits() as $traitFqsen) {
        /** @var Trait_ */
        $trait = $project[(string) $traitFqsen];
        $methods = array_merge($methods, $trait->getMethods());
    }

    // Methods from parent class
    /** @var Class_|null */
    $parentClass = $project[(string) $class->getParent()] ?? null;
    if ($parentClass !== null) {
        $methods = array_merge($methods, getPublicMethods($parentClass, $project));
    }

    return array_filter($methods, function($method) {
        return ($method->getVisibility() == Visibility::PUBLIC_);
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
 * @param  Class_          $class   Class instance
 * @param  Element[string] $project Project files
 * @return string                   Markdown documentation
 */
function renderClass(Class_ $class, array $project): string {
    $doc = "# {$class->getFqsen()}\n\n";

    $methods = [];
    foreach (getPublicMethods($class, $project) as $method) {
        $methods[] = renderMethod($method, $class);
    }
    $doc .= implode("\n---\n\n", $methods);

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
    $doc .= $docblock->getSummary();
    
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
        $doc .= "### Parameters\n";
        foreach ($params as $param) {
            $doc .= "- `\${$param->getVariableName()}`: " . renderType($param->getType(), $class);
            $doc .= "\\\n   {$param->getDescription()}\n";
        }
    }

    // Return type
    if ($return !== null) {
        $doc .= "\n";
        $doc .= "### Returns\n";
        $doc .= "- " . renderType($return->getType(), $class) . "\\\n   {$return->getDescription()}\n";
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
