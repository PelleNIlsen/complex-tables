<?php

class Shortcode_Handler {
    /**
     * Constructor function for the Complex Tables plugin shortcode handler.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function __construct() {
        add_shortcode('complex_table', [$this, 'display_complex_table']);
    }

    /**
     * Generated HTML for displaying a complex table based on the specified shortcode attributes.
     * 
     * @since 1.0.0
     * 
     * @param array $atts   An array of shortcode attributes.
     * 
     * @return string       The HTML for displaying the complex table.
     */
    public function display_complex_table($atts) {
        $atts = shortcode_atts(
            [
                'id' => 0
            ],
            $atts,
            'complex_table'
        );

        $table_id = intval($atts['id']);
        if ($table_id === 0) {
            return '';
        }

        $table = get_post($table_id);
        if (!$table || $table->post_type !== 'complex_table') {
            return '';
        }

        $table_data = $table->post_content;
        $table_css = get_post_meta($table_id, '_complex_tables_custom_css', true);
        $table_class = get_post_meta($table_id, '_complex_tables_class', true);

        $output = $this->generate_table_html($table_data, $table_css, $table_class);

        return $output;
    }

    /**
     * Generates HTML for displaying a complex table based on the specified data and optional CSS and class attributes.
     * 
     * @since 1.0.0
     * 
     * @param string $table_data    The JSON-encoded data representing the table.
     * @param string $table_css     (Optional) The custom CSS to apply to the table.
     * @param string $table_class   (Optional) The CSS class to apply to the table.
     * 
     * @return string               The HTML for displaying the complex table.
     */
    public function generate_table_html($table_data, $table_css = '', $table_class = '') {
        $json_data = json_decode($table_data, true);
        $headers = array_keys($json_data[0]);

        $table_css = preg_replace( '/<.*?>|<|>/', '', $table_css );

        $output = '<style scoped>' . $table_css . '</style>';
        $output .= '<table class="complex-table ' . esc_attr($table_class) . '">';
        $output .= '<thead><tr>';
        foreach ($headers as $header) {
            $output .= '<th>';
            $output .= esc_html($header);
            $output .= '</th>';
        }
        $output .= '</thead></tr><tbody>';
        foreach ($json_data as $row) {
            $output .= '<tr>';
            foreach ($row as $cell) {
                $output .= '<td>';
                $output .= esc_html($cell);
                $output .= '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';

        return $output;
    }
}