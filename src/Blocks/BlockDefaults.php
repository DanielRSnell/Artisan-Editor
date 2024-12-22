<?php
namespace ClientBlocks\Blocks;

class BlockDefaults {
    public static function get_default_php() {
        return <<<'PHP'
<?php
// Query for the latest 3 posts
$recent_posts = get_posts([
    'numberposts' => 3,
    'post_status' => 'publish'
]);

// Add data to the context
$context['greeting'] = 'Hello from the block!';
$context['recent_posts'] = array_map(function($post) {
    return [
        'title' => $post->post_title,
        'url' => get_permalink($post->ID),
        'excerpt' => get_the_excerpt($post)
    ];
}, $recent_posts);

// Return the modified context
return $context;
PHP;
    }
    
    public static function get_default_template() {
        return <<<'TWIG'
<div class="example-block">
    <h2 class="block-title">{{ fields.example_title }}</h2>
    <p>{{ greeting }}</p>
    <div class="block-content">
        <h3>Recent Posts:</h3>
        {% if recent_posts %}
            <ul class="recent-posts">
                {% for post in recent_posts %}
                    <li>
                        <a href="{{ post.url }}">{{ post.title }}</a>
                        <p>{{ post.excerpt }}</p>
                    </li>
                {% endfor %}
            </ul>
        {% else %}
            <p>No recent posts found.</p>
        {% endif %}
        {% if block.inner_blocks %}
            <div class="block-inner-content">
                {{ block.inner_blocks | raw }}
            </div>
        {% endif %}
    </div>
</div>
TWIG;
    }
    
    public static function get_default_css() {
        return <<<'CSS'
.example-block {
    padding: 2rem;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.block-title {
    font-size: 1.5rem;
    color: #333333;
    margin: 0 0 1rem;
}

.block-content {
    color: #666666;
}

.recent-posts {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}

.recent-posts li {
    margin-bottom: 1rem;
}

.recent-posts a {
    font-weight: bold;
    color: #0066cc;
    text-decoration: none;
}

.recent-posts a:hover {
    text-decoration: underline;
}

.block-inner-content {
    margin-top: 2rem;
    padding: 1rem;
    border: 1px dashed #ccc;
    border-radius: 4px;
}
CSS;
    }
    
    public static function get_default_js() {
        return <<<'JS'
document.addEventListener('DOMContentLoaded', function() {
    const block = document.getElementById('{{ block.id }}');
    if (!block) return;
    
    const links = block.querySelectorAll('.recent-posts a');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('You clicked: ' + this.textContent);
        });
    });
});
JS;
    }
}
