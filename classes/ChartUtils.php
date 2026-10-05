<?php
/**
 * Chart Utilities Class
 * Provides Chart.js configuration and data preparation methods
 * Used across all pages that need data visualization
 */

class ChartUtils
{
    // Color palettes
    private static $colors = [
        'gradient1' => ['#667eea', '#764ba2'],
        'gradient2' => ['#f093fb', '#f5576c'],
        'gradient3' => ['#4facfe', '#00f2fe'],
        'palette' => [
            '#667eea', '#764ba2', '#f093fb', '#4facfe', 
            '#00f2fe', '#43e97b', '#fa709a', '#fee140',
            '#8fbcda', '#ff6b6b'
        ],
        'status' => [
            'success' => '#10b981',
            'danger' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
            'primary' => '#667eea'
        ]
    ];

    /**
     * Get color palette
     */
    public static function getColors($type = 'palette')
    {
        return self::$colors[$type] ?? self::$colors['palette'];
    }

    /**
     * Prepare data for line chart (sales trends, stock levels)
     */
    public static function prepareLineChartData($labels, $datasets)
    {
        return [
            'labels' => $labels,
            'datasets' => array_map(function ($dataset, $index) {
                $colors = self::$colors['palette'];
                return [
                    'label' => $dataset['label'],
                    'data' => $dataset['data'],
                    'borderColor' => $colors[$index % count($colors)],
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => $colors[$index % count($colors)],
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointHoverRadius' => 6
                ];
            }, $datasets, array_keys($datasets))
        ];
    }

    /**
     * Prepare data for bar chart (product comparison, sales by category)
     */
    public static function prepareBarChartData($labels, $datasets)
    {
        return [
            'labels' => $labels,
            'datasets' => array_map(function ($dataset, $index) {
                $colors = self::$colors['palette'];
                return [
                    'label' => $dataset['label'],
                    'data' => $dataset['data'],
                    'backgroundColor' => $colors[$index % count($colors)],
                    'borderColor' => 'transparent',
                    'borderRadius' => 4,
                    'borderSkipped' => false
                ];
            }, $datasets, array_keys($datasets))
        ];
    }

    /**
     * Prepare data for pie/doughnut chart (inventory composition)
     */
    public static function preparePieChartData($labels, $data, $type = 'doughnut')
    {
        $colors = self::$colors['palette'];
        
        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => array_slice($colors, 0, count($labels)),
                'borderColor' => '#fff',
                'borderWidth' => 2
            ]]
        ];
    }

    /**
     * Get common chart options with responsive settings
     */
    public static function getCommonOptions($type = 'line')
    {
        $baseOptions = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                            'family' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                        ],
                        'color' => '#374151'
                    ]
                ]
            ]
        ];

        if ($type === 'line') {
            $baseOptions['scales'] = [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)'
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'color' => '#6b7280'
                        ]
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'color' => '#6b7280'
                        ]
                    ]
                ]
            ];
        } elseif ($type === 'bar') {
            $baseOptions['scales'] = [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)'
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'color' => '#6b7280'
                        ]
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                            'color' => '#6b7280'
                        ]
                    ]
                ]
            ];
        } elseif ($type === 'pie' || $type === 'doughnut') {
            $baseOptions['plugins']['legend']['position'] = 'bottom';
        }

        return $baseOptions;
    }

    /**
     * Format tooltip value based on data type
     */
    public static function formatTooltipValue($value, $format = 'number')
    {
        switch ($format) {
            case 'currency':
                return '$' . number_format($value, 2);
            case 'percent':
                return number_format($value, 2) . '%';
            case 'number':
            default:
                return number_format($value);
        }
    }

    /**
     * Generate monthly date labels (last N months)
     */
    public static function getMonthlyLabels($months = 12)
    {
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = date('M Y', strtotime("-$i months"));
        }
        return $labels;
    }

    /**
     * Generate daily date labels (last N days)
     */
    public static function getDailyLabels($days = 30)
    {
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = date('M d', strtotime("-$i days"));
        }
        return $labels;
    }

    /**
     * Build complete Chart.js configuration
     */
    public static function buildChartConfig($type, $data, $title = '', $format = 'number')
    {
        $config = [
            'type' => $type,
            'data' => $data,
            'options' => self::getCommonOptions($type)
        ];

        // Add plugins for tooltip formatting
        if (!isset($config['options']['plugins']['tooltip'])) {
            $config['options']['plugins']['tooltip'] = [];
        }

        $config['options']['plugins']['tooltip']['callbacks'] = [
            'label' => "function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += '" . self::formatTooltipValue(100, $format) . "'.replace('100', context.parsed.y);
                } else if (context.parsed !== null) {
                    label += '" . self::formatTooltipValue(100, $format) . "'.replace('100', context.parsed);
                }
                return label;
            }"
        ];

        return $config;
    }
}
?>
