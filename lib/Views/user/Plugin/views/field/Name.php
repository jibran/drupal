<?php

/**
 * @file
 * Definition of Views\user\Plugin\views\field\Name.
 */

namespace Views\user\Plugin\views\field;

use Views\user\Plugin\views\field\User;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide simple renderer that allows using a themed user link.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "user_name",
 *   module = "user"
 * )
 */
class Name extends User {

  /**
   * Add uid in the query so we can test for anonymous if needed.
   */
  function init(&$view, &$data) {
    parent::init($view, $data);
    if (!empty($this->options['overwrite_anonymous']) || !empty($this->options['format_username'])) {
      $this->additional_fields['uid'] = 'uid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['overwrite_anonymous'] = array('default' => FALSE, 'bool' => TRUE);
    $options['anonymous_text'] = array('default' => '', 'translatable' => TRUE);
    $options['format_username'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['format_username'] = array(
      '#title' => t('Use formatted username'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['format_username']),
      '#description' => t('If checked, the username will be formatted by the system. If unchecked, it will be displayed raw.'),
      '#fieldset' => 'more',
    );
    $form['overwrite_anonymous'] = array(
      '#title' => t('Overwrite the value to display for anonymous users'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['overwrite_anonymous']),
      '#description' => t('Enable to display different text for anonymous users.'),
      '#fieldset' => 'more',
    );
    $form['anonymous_text'] = array(
      '#title' => t('Text to display for anonymous users'),
      '#type' => 'textfield',
      '#default_value' => $this->options['anonymous_text'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[overwrite_anonymous]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'more',
    );

    parent::buildOptionsForm($form, $form_state);
  }

  function render_link($data, $values) {
    $account = entity_create('user', array());
    $account->uid = $this->get_value($values, 'uid');
    $account->name = $this->get_value($values);
    if (!empty($this->options['link_to_user']) || !empty($this->options['overwrite_anonymous'])) {
      if (!empty($this->options['overwrite_anonymous']) && !$account->uid) {
        // This is an anonymous user, and we're overriting the text.
        return check_plain($this->options['anonymous_text']);
      }
      elseif (!empty($this->options['link_to_user'])) {
        $account->name = $this->get_value($values);
        return theme('username', array('account' => $account));
      }
    }
    // If we want a formatted username, do that.
    if (!empty($this->options['format_username'])) {
      return user_format_name($account);
    }
    // Otherwise, there's no special handling, so return the data directly.
    return $data;
  }

}
