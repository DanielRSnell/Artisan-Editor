<?php
$context = [];
$context['block'] = $block;
$context['fields'] = get_fields();
$context['example_variable'] = 'This is an example';
if (isset($context['fields']['title'])) {
    $context['fields']['title'] = strtoupper($context['fields']['title']);
}
$context['custom_function'] = function ($param) {
    return "Processed: " . $param;
};

Timber::render('@block/{{block_slug}}/block.twig', $context);
