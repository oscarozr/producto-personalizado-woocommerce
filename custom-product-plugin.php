<?php
/*
Plugin Name: Custom Product Plugin
Description: Permite la personalización de productos, eligiendo por categorías de productos cuales quieres permitir que se active la función.
Version: 1.0
Author: Oscar Jesús Zúñiga Ruiz
*/

// Evita el acceso directo.
if (!defined('ABSPATH')) {
    exit;
}

// Cargar scripts y estilos
function cpp_enqueue_scripts() {
    wp_enqueue_script('cpp-custom-script', plugins_url('/js/custom-script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'cpp_enqueue_scripts');

// Agregar campos de personalización en el panel de producto
function cpp_add_custom_text_field() {
    global $product;

    // Verifica si el producto pertenece a una categoría seleccionada
    $custom_categories = get_option('cpp_custom_categories', array());
    if (array_intersect($custom_categories, wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids')))) {
        echo '<input type="text" name="custom_message" placeholder="Enter your message" />';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'cpp_add_custom_text_field');

// Guardar el mensaje personalizado al agregar el producto al carrito
function cpp_save_custom_text_field($cart_item_data, $product_id) {
    if (isset($_POST['custom_message'])) {
        $cart_item_data['custom_message'] = sanitize_text_field($_POST['custom_message']);
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'cpp_save_custom_text_field', 10, 2);

// Mostrar el mensaje personalizado en el carrito
function cpp_display_custom_message_cart($item_data, $cart_item) {
    if (isset($cart_item['custom_message'])) {
        $item_data[] = array(
            'name' => 'Custom Message',
            'value' => $cart_item['custom_message']
        );
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'cpp_display_custom_message_cart', 10, 2);

// Guardar el mensaje personalizado en el pedido
function cpp_save_custom_message_order($item, $cart_item_key, $values, $order) {
    if (isset($values['custom_message'])) {
        $item->add_meta_data('Custom Message', $values['custom_message'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'cpp_save_custom_message_order', 10, 4);

// Agregar opciones en el admin para seleccionar categorías
function cpp_admin_menu() {
    add_options_page('Custom Product Plugin Settings', 'Custom Product Plugin', 'manage_options', 'custom-product-plugin', 'cpp_settings_page');
}
add_action('admin_menu', 'cpp_admin_menu');

function cpp_settings_page() {
    if (isset($_POST['cpp_save_settings'])) {
        $selected_categories = isset($_POST['cpp_categories']) ? array_map('sanitize_text_field', $_POST['cpp_categories']) : array();
        update_option('cpp_custom_categories', $selected_categories);
        echo '<div class="updated"><p>Settings saved</p></div>';
    }

    $categories = get_terms('product_cat', array('hide_empty' => false));
    $selected_categories = get_option('cpp_custom_categories', array());

    ?>
    <div class="wrap">
        <h1>Custom Product Plugin Settings</h1>
        <form method="post" action="">
            <h2>Select Product Categories</h2>
            <?php foreach ($categories as $category): ?>
                <label>
                    <input type="checkbox" name="cpp_categories[]" value="<?php echo esc_attr($category->term_id); ?>" <?php checked(in_array($category->term_id, $selected_categories)); ?> />
                    <?php echo esc_html($category->name); ?>
                </label><br />
            <?php endforeach; ?>
            <input type="submit" name="cpp_save_settings" value="Save Settings" class="button-primary" />
        </form>
    </div>
    <?php
}
