<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationLabNotesForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Mail\MailManagerInterface;

class LabMigrationLabNotesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_lab_notes_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    // $proposal_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('proposal_id');
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d LIMIT 1", $proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      \Drupal::messenger()->addmessage(t('Invalid lab selected. Please try again.'), 'error');
      // RedirectResponse('lab-migration/code-approval');// Generate the URL from the route.
$url = Url::fromRoute('lab_migration.code_approval')->toString();

// Create the redirect response.
$response = new RedirectResponse($url);

// Send the response.
$response->send();

      return;
    }
    /* get current notes */
    $notes = '';
    //$notes_q = $injected_database->query("SELECT * FROM {lab_migration_notes} WHERE proposal_id = %d LIMIT 1", $proposal_id);
    $query = $injected_database->select('lab_migration_notes');
    $query->fields('lab_migration_notes');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    if ($notes_q) {
      $notes_data = $notes_q->fetchObject();
      $notes = $notes_data->notes;
    }
    $form['lab_details'] = [
      '#type' => 'item',
      '#value' => '<span style="color: rgb(128, 0, 0);"><strong>About the Lab</strong></span><br />' . '<strong>Proposer:</strong> ' . $proposal_data->name . '<br />' . '<strong>Title of the Lab:</strong> ' . $proposal_data->lab_title . '<br />',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => t('Notes for Reviewers'),
      '#default_value' => $notes,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => Link::fromTextAndUrl(t('Back'), 'lab-migration/code-approval'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = (int) arg(3);
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d LIMIT 1", $proposal_id);
    $query = $injected_database->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $proposal_id);
    $query->range(0, 1);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if (!$proposal_data) {
      add_message(t('Invalid lab selected. Please try again.'), 'error');
      RedirectResponse('lab-migration/code-approval');
      return;
    }
    /* find existing notes */
    //$notes_q = $injected_database->query("SELECT * FROM {lab_migration_notes} WHERE proposal_id = %d LIMIT 1", $proposal_id);
    $query = $injected_database->select('lab_migration_notes');
    $query->fields('lab_migration_notes');
    $query->condition('proposal_id', $proposal_id);
    $query->range(0, 1);
    $notes_q = $query->execute();
    $notes_data = $notes_q->fetchObject();
    /* add or update notes in database */
    if ($notes_data) {
      $query = "UPDATE {lab_migration_notes} SET notes = :notes WHERE id = :notes_id";
      $args = [
        ":notes" => $form_state->getValue(['notes']),
        ":notes_id" => $notes_data->id,
      ];
      $injected_database->query($query, $args);
      add_message('Notes updated successfully.', 'status');
    }
    else {
      $query = "INSERT INTO {lab_migration_notes} (proposal_id, notes) VALUES (:proposal_id, :notes)";
      $args = [
        ":proposal_id" => $proposal_id,
        ":notes" => $form_state->getValue(['notes']),
      ];
      $injected_database->query($query, $args);
      add_message('Notes added successfully.', 'status');
    }
  }

}

