<?php
/**
 * \file
 * \biref Simple Mardown to HTML utility file.
 * 
 * @author HugoFara <hugo.farajallah@protonmail.com>
 * @since  2.0.3-fork
 */
require __DIR__ . '/../../vendor/autoload.php';

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Convert a markdown file to HTML and return the result.
 * 
 * @param string $file_path Full path for the file to use, including extension.
 * 
 * @return string An HTML-formatted string
 */
function markdown_converter($file_path) 
{
    $converter = new GithubFlavoredMarkdownConverter();
    $markdown = file_get_contents($file_path);
    return (string)$converter->convertToHtml($markdown);
}
?>