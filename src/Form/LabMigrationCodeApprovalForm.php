<?php

/**
 * @file
 * Contains \Drupal\lab_migration\Form\LabMigrationCodeApprovalForm.
 */

namespace Drupal\lab_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\Mail\MailManagerInterface;

class LabMigrationCodeApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lab_migration_code_approval_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // $solution_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

    $solution_id = (int) $route_match->getParameter('solution_id');
    $injected_database = \Drupal::database();
    /* get solution details */
    //$solution_q = $injected_database->query("SELECT * FROM {lab_migration_solution} WHERE id = %d", $solution_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data) {
      \Drupal::messenger()->addStatus(t('Invalid solution selected.'));

      // RedirectResponse('lab-migration/code-approval');
      $url = Url::fromRoute('lab_migration.code_approval')->toString(); // Replace with the actual route name
$response = new RedirectResponse($url);

// Return the redirect response
return $response;
    }
    if ($solution_data->approval_status == 1) {
      \Drupal::messenger()->addmessage(t('This solution has already been approved. Are you sure you want to change the approval status?'), 'error');
    }
    if ($solution_data->approval_status == 2) {
      \Drupal::messenger()->addmessage(t('This solution has already been dis-approved. Are you sure you want to change the approval status?'), 'error');
    }
    /* get experiment data */
    //xperiment_q = $injected_database->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $solution_data->experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    /* get proposal data */
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $experiment_data->proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    /* get solution provider details */
    $solution_provider_user_name = $proposal_data->solution_provider_name;
    // $user_data = loadMultiple($proposal_data->solution_provider_uid);
    
    // $user_data = User::load($proposal_data->solution_provider_uid);
    // $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->solution_provider_uid);
    // if ($user_data) {
    //   $solution_provider_user_name = $user_data->name;
    // }
    // else {
      // $solution_provider_user_name = '';
    // }
    $form['#tree'] = TRUE;
    $form['lab_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->lab_title,
      '#title' => t('Title of the Lab'),
    ];
    $form['name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->name,
      '#title' => t('Contributor Name'),
    ];
    $form['experiment']['number'] = [
      '#type' => 'item',
      '#markup' => $experiment_data->number,
      '#title' => t('Experiment Number'),
    ];
    $form['experiment']['title'] = [
      '#type' => 'item',
      '#markup' => $experiment_data->title,
      '#title' => t('Title of the Experiment'),
    ];
    $form['back_to_list'] = [
      '#type' => 'item',
      // '#markup' => Link::fromTextAndUrl('Back to Code Approval List', 'lab-migration/code-approval'),
    '#markup' => Link::fromTextAndUrl('Back to Code Approval List', Url::fromRoute('lab_migration.code_approval'))->toString(),
   

  ];
    $form['code_number'] = [
      '#type' => 'item',
      '#markup' => $solution_data->code_number,
      '#title' => t('Code No'),
    ];
    $form['code_caption'] = [
      '#type' => 'item',
      '#markup' => $solution_data->caption,
      '#title' => t('Caption'),
    ];
    /* get solution files */
    $solution_files_html = '';
    //$solution_files_q = $injected_database->query("SELECT * FROM {lab_migration_solution_files} WHERE solution_id = %d ORDER BY id ASC", $solution_id);
    $query = \Drupal::database()->select('lab_migration_solution_files');
    $query->fields('lab_migration_solution_files');
    $query->condition('solution_id', $solution_id);
    $query->orderBy('id', 'ASC');
    $solution_files_q = $query->execute();
    if ($solution_files_q) {
      while ($solution_files_data = $solution_files_q->fetchObject()) {
        $code_file_type = '';
        switch ($solution_files_data->filetype) {
          case 'S':
            $code_file_type = 'Source';
            break;
          case 'R':
            $code_file_type = 'Result';
            break;
          case 'X':
            $code_file_type = 'Xcox';
            break;
          case 'U':
            $code_file_type = 'Unknown';
            break;
          default:
            $code_file_type = 'Unknown';
            break;
        }
        $url = Url::fromUri('internal:/lab-migration/download/solution/' . $solution_files_data->id);

// Create the link with Link::fromTextAndUrl.
$link = Link::fromTextAndUrl($solution_files_data->filename, $url)->toString();

// Append the link and additional HTML.
$solution_files_html .= $link . ' (' . $code_file_type . ')<br/>';
       
        /*if(strlen($solution_files_data->pdfpath)>=5){
            $pdfname=substr($solution_files_data->pdfpath, strrpos($solution_files_data->pdfpath, '/') + 1);
            $solution_files_html .=l($pdfname, 'lab-migration/download/pdf/' . $solution_files_data->id). ' (PDF File)' . '<br/>';
            }*/
      }
    }
    /* get dependencies files */
    // $dependency_q = $injected_database->query("SELECT * FROM {lab_migration_solution_dependency} WHERE solution_id = %d ORDER BY id ASC", $solution_id);
    // $query = \Drupal::database()->select('lab_migration_solution_dependency');
    // $query->fields('lab_migration_solution_dependency');
    // $query->condition('solution_id', $solution_id);
    // $query->orderBy('id', 'ASC');
    // $dependency_q = $query->execute();
    // while ($dependency_data = $dependency_q->fetchObject()) {
    //   //$dependency_files_q = $injected_database->query("SELECT * FROM {lab_migration_dependency_files} WHERE id = %d", $dependency_data->dependency_id);
    //   $query = \Drupal::database()->select('lab_migration_dependency_files');
    //   $query->fields('lab_migration_dependency_files');
    //   $query->condition('id', $dependency_data->dependency_id);
    //   $dependency_files_q = $query->execute();
    //   $dependency_files_data = $dependency_files_q->fetchObject();
    //   $solution_file_type = 'Dependency file';
    //   $solution_files_html .= Link::fromTextAndUrl($dependency_files_data->filename, 'lab-migration/download/dependency/' . $dependency_files_data->id) . ' (' . 'Dependency' . ')' . '<br/>';
    // }
    $form['solution_files'] = [
      '#type' => 'item',
      '#markup' => $solution_files_html,
      '#title' => t('Solution'),
    ];
    $form['approved'] = [
      '#type' => 'radios',
      '#options' => [
        '0' => 'Pending',
        '1' => 'Approved',
        '2' => 'Dis-approved (Solution will be deleted)',
      ],
      '#title' => t('Approval'),
      '#default_value' => $solution_data->approval_status,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for dis-approval'),
      '#states' => [
        'visible' => [
          ':input[name="approved"]' => [
            'value' => '2'
            ]
          ],
        'required' => [':input[name="approved"]' => ['value' => '2']],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'markup',
      // '#markup' => Link::fromTextAndUrl(t('Cancel'), 'lab_migration/code_approval'),
      '#markup' => Link::fromTextAndUrl( t('Cancel'), 
         Url::fromRoute('lab_migration.code_approval'))->toString(),

    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['approved']) == 2) {
      if (strlen(trim($form_state->getValue(['message']))) <= 30) {
        $form_state->setErrorByName('message', t('Please mention the reason for disapproval.'));
      }
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    // $user->get('uid')->value;


    // $solution_id = (int) arg(3);
    $route_match = \Drupal::routeMatch();

$solution_id = (int) $route_match->getParameter('solution_id');
    /* get solution details */
    //$solution_q = $injected_database->query("SELECT * FROM {lab_migration_solution} WHERE id = %d", $solution_id);
    $query = \Drupal::database()->select('lab_migration_solution');
    $query->fields('lab_migration_solution');
    $query->condition('id', $solution_id);
    $solution_q = $query->execute();
    $solution_data = $solution_q->fetchObject();
    if (!$solution_data) {
      \Drupal::messenger()->addmessage(t('Invalid solution selected.'), 'status');
      RedirectResponse('lab_migration/code_approval');
    }
    /* get experiment data */
    //$experiment_q = $injected_database->query("SELECT * FROM {lab_migration_experiment} WHERE id = %d", $solution_data->experiment_id);
    $query = \Drupal::database()->select('lab_migration_experiment');
    $query->fields('lab_migration_experiment');
    $query->condition('id', $solution_data->experiment_id);
    $experiment_q = $query->execute();
    $experiment_data = $experiment_q->fetchObject();
    /* get proposal data */
    //$proposal_q = $injected_database->query("SELECT * FROM {lab_migration_proposal} WHERE id = %d", $experiment_data->proposal_id);
    $query = \Drupal::database()->select('lab_migration_proposal');
    $query->fields('lab_migration_proposal');
    $query->condition('id', $experiment_data->proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    // $user_data = loadMultiple($proposal_data->uid);

    $user_data = User::load($proposal_data->uid);   
    $approver_uid = $user->id();

    //  $solution_prove_user_data =User::loadMultiple($proposal_data->solution_provider_uid);
    // **** TODO **** : del_lab_pdf($proposal_data->id);
    if ($form_state->getValue(['approved']) == "0") {
      $query = "UPDATE {lab_migration_solution} SET approval_status = 0, approver_uid = :approver_uid, approval_date = :approval_date WHERE id = :solution_id";
      $args = [
        ":approver_uid" => $approver_uid,
        ":approval_date" => time(),
        ":solution_id" => $solution_id,
      ];
      \Drupal::database()->query($query, $args);
      /* sending email */

// Recipient email
$email_to = $user_data->getEmail(); // Drupal 10 method

// Get mail config
$from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
$bcc  = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
$cc   = \Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');

// Prepare mail parameters
$param['solution_pending'] = [
    'solution_id' => $solution_id,
    'user_id'     => $user_data->id(),
    'headers' => [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => is_array($cc) ? implode(',', $cc) : $cc,
        'Bcc' => is_array($bcc) ? implode(',', $bcc) : $bcc,
    ],
];

// Language code
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

// Send email
$mail_manager = \Drupal::service('plugin.manager.mail');
$result = $mail_manager->mail(
    'lab_migration',       // Module name
    'solution_pending',    // Mail key
    $email_to,             // Recipient
    $langcode,             // Language code
    $param,                // Parameters
    NULL,                  // Reply-to
    TRUE                   // Send immediately
);

// Error handling
if (!$result['result']) {
    \Drupal::messenger()->addError('Error sending email message.');
}

    }
    else {
      if ($form_state->getValue(['approved']) == "1") {
        $query = "UPDATE {lab_migration_solution} SET approval_status = 1, approver_uid = :approver_uid, approval_date = :approval_date WHERE id = :solution_id";
        $args = [
          ":approver_uid" => $approver_uid,
          ":approval_date" => time(),
          ":solution_id" => $solution_id,
        ];
        \Drupal::database()->query($query, $args);
        /* sending email */
$email_to = $user_data->getEmail(); // Drupal 10 method

// Get config values
$from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
$bcc  = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
$cc   = \Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');

// Prepare mail parameters
$param['solution_approved'] = [
    'solution_id' => $solution_id,
    'user_id'     => $user_data->id(),
    'headers' => [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => is_array($cc) ? implode(',', $cc) : $cc,
        'Bcc' => is_array($bcc) ? implode(',', $bcc) : $bcc,
    ],
];

// Language code
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

// Send mail
$mail_manager = \Drupal::service('plugin.manager.mail');
$result = $mail_manager->mail(
    'lab_migration',        // Module name
    'solution_approved',    // Mail key
    $email_to,              // Recipient
    $langcode,              // Language
    $param,                 // Parameters
    NULL,                   // Reply-to
    TRUE                    // Send immediately
);

// Error handling
if (!$result['result']) {
    \Drupal::messenger()->addError('Error sending email message.');
}
      }
      else {
        if ($form_state->getValue(['approved']) == "2") {
          if (\Drupal::service("lab_migration_global")->lab_migration_delete_solution($solution_id)) {
            /* sending email */

// Recipient email
$email_to = $user_data->getEmail();

// Mail configuration
$from = \Drupal::config('lab_migration.settings')->get('lab_migration_from_email');
$bcc  = \Drupal::config('lab_migration.settings')->get('lab_migration_emails');
$cc   = \Drupal::config('lab_migration.settings')->get('lab_migration_cc_emails');

// Prepare mail parameters
$param['solution_disapproved'] = [
  'solution_id'       => $proposal_data->id,
  'experiment_number' => $experiment_data->number,
  'experiment_title'  => $experiment_data->title,
  'solution_number'   => $solution_data->code_number,
  'solution_caption'  => $solution_data->caption,
  'user_id'           => $user_data->id(),
  'message'           => $form_state->getValue(['message']),
  'headers' => [
    'From' => $from,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
    'Content-Transfer-Encoding' => '8Bit',
    'X-Mailer' => 'Drupal',
    'Cc' => is_array($cc) ? implode(',', $cc) : $cc,
    'Bcc' => is_array($bcc) ? implode(',', $bcc) : $bcc,
  ],
];

// Language code
$langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

// Send mail
$mail_manager = \Drupal::service('plugin.manager.mail');
$result = $mail_manager->mail(
  'lab_migration',            // Module
  'solution_disapproved',     // Mail key
  $email_to,                  // Recipient
  $langcode,                  // Language
  $param,                     // Params
  NULL,                       // Reply-to
  TRUE                        // Send immediately
);

// Error handling
if (!$result['result']) {
  \Drupal::messenger()->addError('Error sending email message.');
}
  }
          else {
            \Drupal::messenger()->addError('Error disapproving and deleting solution. Please contact administrator.', 'error');
          }
        }
      }
    }
    \Drupal::messenger()->addmessage('Updated successfully.', 'status');
    // RedirectResponse('lab-migration/code-approval');
    $response = new RedirectResponse(Url::fromRoute('lab_migration.code_approval')->toString());
  
  // Send the redirect response
  $response->send();
  
  }

}

