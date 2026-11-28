<?php

$dir = __DIR__ . '/app/Models/';
$files = glob($dir . '*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $modelName = basename($file, '.php');
    
    // Match public function name(): RelationClass
    $pattern = '/public function ([a-zA-Z0-9_]+)\(\):\s*(BelongsTo|HasMany|HasOne|BelongsToMany|MorphTo|MorphMany)/';
    
    $content = preg_replace_callback($pattern, function($matches) use ($content, $modelName) {
        $funcName = $matches[1];
        $relation = $matches[2];
        
        // Find the return class inside the method body.
        // e.g. return $this->belongsTo(RelatedModel::class
        $bodyPattern = '/public function ' . $funcName . '\(\):\s*' . $relation . '\s*\{[^\}]*return \$this->[a-zA-Z0-9_]+\(\s*([a-zA-Z0-9_]+)::class/s';
        
        if (preg_match($bodyPattern, $content, $bodyMatches)) {
            $relatedModel = $bodyMatches[1];
            return "/** @return {$relation}<{$relatedModel}, \$this> */\n    public function {$funcName}(): {$relation}";
        }
        
        return $matches[0];
    }, $content);
    
    file_put_contents($file, $content);
}

echo "Generics fixed.\n";
