<?php

namespace Drupal\rest_api_authentication;
/**
 * Provides a ready-made table with ajax add/remove rows button.
 *
 * This class provides you three functionality:
 *
 *    (1) Generate Tables
 *
 *    (2) Generate Add more button
 *
 *    (3) Generate unique id array
 *
 * - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * (1) Generate Tables:
 * You can generate simple table by providing few parameters to function generateTables()
 * This function required following parameters:
 * (i) ID: Unique ID of table useful for ajax operations
 * (ii) fields: Number of fields in table. You can add as many fields as you want. field should be an array in following format
 * @code
 * $fields = [
 *  'first_name' => [
 *     'type' => 'select',
 *  ],
 * 'last_name' => [
 *    'type' => 'textfield',
 *    'placeholder' => 'Last Name of User'
 *  ],
 * 'delete_button' => [
 *    'type' => 'submit',
 *    'submit' => '::removeCallback',
 *    'callback' => '::removeButtonCallback',
 *    'wrapper' => 'names-fieldset-wrapper',
 *    ]
 *  ];
 * (iii) Unique ID array: This is the array which contains array of unique array to print rows
 * (iv) Options: default options to show in the  table
 * (v) Headers (optional): header of the table in the array format
 * (vi) Option to show in drop-down list (optional): This should be an array with following format:
 * @code
 * $select_list = [
 *  1 => [
 *    'first_column_row_1' => 'one',
 *    'first_column_row_2' => 'two',
 *    'first_column_row_3' => 'three',
 *    'first_column_row_4' => 'four',
 *  ]
 *  2 => [
 *    'second_column_row_1' => 'one',
 *    'second_column_row_2' => 'two',
 *    'second_column_row_3' => 'three',
 *    'second_column_row_4' => 'four',
 *  ]
 * ]
 * In above array $select_list -> 1 and 2 denoted column number where you want to show drop-down select list
 *
 * - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * (2) Generate Add more button:
 * You can easily generate add more button by providing few parameters to generateAddButton() function
 * You need to provide name of button, submit function, callback function, text before button etc.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * (3) Generate unique id array:
 * Unique id array contains array key of each table row, and it is used to print rows of table.
 * This array updates on every ajax call. Use generateAddButton() function to generate this array.
 */

class AjaxTables
{

  /**
   * Main function to generate tables
   *
   * @param $id
   * ID of the table, useful for ajax wrapper
   * @param $fields
   * Array of fields to be used in table
   * @param $unique_array
   * Array contains index of rows
   * @param $options
   * Main data to be print in table
   * @param array $headers
   * Header of the table
   * @param array $select_list
   * Options for select element (optional)
   *
   * @return array
   * Returns complete table
   */
  public static function generateTables($id, $fields, $unique_array, $options, array $headers = [], array $select_list = [])
  {
    $form['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#prefix' => '<div id="' . $id . '">',
      '#suffix' => '</div>',
    );

    foreach ($unique_array as $row) {
      $form['table'][$row] = static::generateFields($fields, $row, $select_list);
      foreach ($fields as $key => $value) {
        if ($value['type'] != 'submit') {
          $form['table'][$row][$key]['#default_value'] = $options[$row][$key] ?? '';
        }
      }
    }

    return $form['table'];
  }

  /**
   * The $option parameter should be following format
   * $option = ['unique_id' => ['field_name' => 'field_value']]
   * @param $form_state_value
   * Value of unique array in form_state
   * @param $options
   * The options array to be print in table format
   * @return int[]|string[]
   * Returns array of unique id array
   */
  public static function getUniqueID($form_state_value, $options)
  {
    if (empty($form_state_value)) {
      $form_state_value = array_keys($options);
      if (empty($form_state_value)) {
        $uuid_service = \Drupal::service('uuid');
        $form_state_value[] = $uuid_service->generate();
      }
    }
    return $form_state_value;
  }


  /**
   * @param $fields
   * Fields to be generated
   * @param $row
   * Unique ID of the row
   * @param $select_list
   * Options for form type select (optional)
   * @return array
   */
  private static function generateFields($fields, $row, $select_list)
  {
    $form = [];
    $count = 1;
    foreach ($fields as $key => $value) {
      $row_data = [];

      foreach ($value as $form_element => $form_value) {
        $row_data['#' . $form_element] = $form_value;
      }

      $form[$key] = $row_data;

      if ($value['type'] == 'select') {
        $form[$key]['#options'] = $select_list[$count];

      }

      if ($value['type'] == 'submit') {
        unset($form[$key]['#callback']);
        unset($form[$key]['#wrapper']);
        unset($form[$key]['#submit']);
        $form[$key]['#type'] = 'submit';
        $form[$key]['#value'] = 'Remove';
        $form[$key]['#name'] = $row;
        $form[$key]['#submit'] = [$value['submit']];
        $form[$key]['#ajax'] = [
          'callback' => $value['callback'],
          'wrapper' => $value['wrapper'],
          'progress' => [
            'message' => NULL,
          ]
        ];
      }
      $count++;
    }
    return $form;
  }

  /**
   * This function is automatically generate ajax add more button.
   * If you have to generate more than one buttons on same form then use different name for button
   *
   * @param $value
   * Value of the add button
   * @param $submit
   * Function to call of ajax call where logic of add row is written.
   *  Function name should be in '::nameOfFunction' format
   * @param $callback
   * Callback function in ajax call.
   *  Function name should be in '::callbackFunction' format
   * @param $wrapper
   * An ajax wrapper
   * @param string $text_before_button
   * Use this parameter If you have to add some text before the add button (optional)
   * @param bool $disabled
   * Status of button i.e disabled or enabled
   * @return array
   * Ajax add more button
   */
  public static function generateAddButton($value, $submit, $callback, $wrapper, string $text_before_button = '', bool $disabled = false)
  {
    $form['text_before_button_' . $wrapper] = [
      '#markup' => t('<b>' . $text_before_button . '&#8194; </b>'),
      '#prefix' => '<div class="container-inline">',
    ];

    $form['button_' . $wrapper] = [
      '#type' => 'submit',
      '#value' => $value,
      '#name' => $wrapper,
      '#submit' => [$submit],
      '#disabled' => $disabled,
      '#ajax' => [
        'callback' => $callback,
        'wrapper' => $wrapper,
        'progress' => [
          'message' => NULL,
        ]
      ],

    ];

    $form['total_rows_' . $wrapper] = [
      '#type' => 'number',
      '#disabled' => $disabled,
      '#default_value' => 1,
      '#min' => 1,
      '#max' => 50,

    ];

    $form['rows_markup'] = [
      '#type' => 'item',
      '#markup' => 'more rows',
      '#prefix' => '&nbsp;&nbsp;',
      '#suffix' => '</div>'
    ];
    return $form;
  }
}

