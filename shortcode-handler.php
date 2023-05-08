<?php

class Shortcode_Handler {
    public function __construct() {
        add_shortcode('complex_table', array($this, 'display_complex_table'));
    }

    public function display_complex_table($atts) {
        $atts = shortcode_atts(
            array(
                'id' => 0
            ),
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

    public function generate_table_html($table_data, $table_css = '', $table_class = '') {
        $json_data = json_decode($table_data, true);
        $headers = array_keys($json_data[0]);

        $output = '<style scoped>' . $table_css . '</style>';
        $output .= '<table class="complex-table ' . esc_attr($table_class) . '">';
        $output .= '<thead><tr>';
        foreach ($headers as $header) {
            $output .= '<th>';
            $output .= htmlspecialchars($header);
            $output .= '</th>';
        }
        $output .= '</thead></tr><tbody>';
        foreach ($json_data as $row) {
            $output .= '<tr>';
            foreach ($row as $cell) {
                $output .= '<td>';
                $output .= htmlspecialchars($cell);
                $output .= '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';

        return $output;
    }
}