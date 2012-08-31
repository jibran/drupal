<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\HistoryUserTimestamp.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\Node;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to display the marker for new content.
 *
 * The handler is named history_user, because of compability reasons, the table
 * is history.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_history_user_timestamp",
 *   module = "node"
 * )
 */
class HistoryUserTimestamp extends Node {

  function init(&$view, &$options) {
    parent::init($view, $options);
    global $user;
    if ($user->uid) {
      $this->additional_fields['created'] = array('table' => 'node', 'field' => 'created');
      $this->additional_fields['changed'] = array('table' => 'node', 'field' => 'changed');
      if (module_exists('comment') && !empty($this->options['comments'])) {
        $this->additional_fields['last_comment'] = array('table' => 'node_comment_statistics', 'field' => 'last_comment_timestamp');
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['comments'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (module_exists('comment')) {
      $form['comments'] = array(
        '#type' => 'checkbox',
        '#title' => t('Check for new comments as well'),
        '#default_value' => !empty($this->options['comments']),
        '#fieldset' => 'more',
      );
    }
  }

  public function query() {
    // Only add ourselves to the query if logged in.
    global $user;
    if (!$user->uid) {
      return;
    }
    parent::query();
  }

  function render($values) {
    // Let's default to 'read' state.
    // This code shadows node_mark, but it reads from the db directly and
    // we already have that info.
    $mark = MARK_READ;
    global $user;
    if ($user->uid) {
      $last_read = $this->get_value($values);
      $changed = $this->get_value($values, 'changed');

      $last_comment = module_exists('comment') && !empty($this->options['comments']) ?  $this->get_value($values, 'last_comment') : 0;

      if (!$last_read && $changed > NODE_NEW_LIMIT) {
        $mark = MARK_NEW;
      }
      elseif ($changed > $last_read && $changed > NODE_NEW_LIMIT) {
        $mark = MARK_UPDATED;
      }
      elseif ($last_comment > $last_read && $last_comment > NODE_NEW_LIMIT) {
        $mark = MARK_UPDATED;
      }
      return $this->render_link(theme('mark', array('type' => $mark)), $values);
    }
  }

}
