<?php

/**
 * @file
 *  RusaMemberEditForm.php
 *
 * @Created 
 *  2018-03-13 - Paul Lieberman
 *
 * RUSA member edit Form. 
 *
 * ----------------------------------------------------------------------------------------
 * 
 */

namespace Drupal\rusa_member\Form;

use Drupal\rusa_api\RusaOfficials;
use Drupal\rusa_api\RusaMembers;
use Drupal\rusa_api\RusaClubs;
use Drupal\rusa_api\RusaStates;
use Drupal\rusa_api\RusaCountries;
use Drupal\rusa_api\Client\RusaClient;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RusaMemberEditForm
 *
 * This is the Drupal Form class.
 * All of the form handling is within this class.
 *
 */
class RusaMemberEditForm extends FormBase {

  protected $currentUser;
  protected $messenger;

  /**
   * @getFormID
   *
   * Required
   *
   */
  public function getFormId() {
    return 'rusa_member_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
    $this->messenger   = \Drupal::messenger();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * @buildForm
   *
   * Build an edit form for personal information.
   * Use veritical tabs for member and official.
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {  
   
    // Get logged in user's RUSA ID
    $user = User::load($this->currentUser->id());
    $mid  = $user->get('field_rusa_member_id')->getValue()[0]['value'];

    if (empty($mid)) {
      // This should never happen as they should get an access denied first
      $this->messenger->addMessage(t("You must be logged in and have a RUSA # to use this form."), $this->messenger::TYPE_ERROR);
      $form_state->setRedirect('rusa_home');
      return;
    }

    // Get the list of clubs
    $clobj = new RusaClubs();
    $clubs = $clobj->getClubsSelect();
    asort($clubs);

    // Get a list of states for the address
    $sobj   = new RusaStates();
    $states = $sobj->getStates(3);  // 3 means include territoreis, military POs and CA provences.

    // Get a list of countries for the address
    $cobj = new RusaCountries();
    $countries = $cobj->getCountries();

    // Get the member data for this person
    $mobj  = new RusaMembers(['key' => 'mid', 'val' => $mid]);
    $member = $mobj->getMember($mid);

    // Build the form
    $form['rusa'] = [
      '#type'        => 'vertical_tabs',
      '#default_tab' => 'member',
    ];

    $form['member'] = [
      '#type'   => 'details',
      '#title'  => $this->t('RUSA Member'),
      '#group'  => 'rusa',
    ];

    $form['member']['fname'] = [
      '#type'           => 'textfield',
      '#title'          => 'First name',
      '#default_value'  => $member->fname,
    ];

    $form['member']['mname'] = [
      '#type'           => 'textfield',
      '#title'          => 'Middle name',
      '#default_value'  => $member->mname,
    ];

    $form['member']['sname'] = [
      '#type'           => 'textfield',
      '#title'          => 'Last name',
      '#default_value'  => $member->sname,
    ];

    $form['member']['gender'] = [
      '#type'           => 'select',
      '#title'          => 'Gender',
      '#default_value'  => $member->gender,
      '#options'        => [
        'M' => 'Male',
      'F' => 'Female',
      'N' => 'Non binary',
      ],
    ];

    $form['member']['phone'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Phone'),
      '#default_value'  => $member->phone,
    ];

    $form['member']['email'] = [
      '#type'           => 'email',
      '#title'          => $this->t('E-mail'),
      '#default_value'  => $member->email,
    ];

    $form['member']['address'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Address'),
      '#default_value'  => $member->address,
    ];

    $form['member']['city'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('City'),
      '#default_value'  => $member->city,
    ];

    $form['member']['state'] = [
      '#type'           => 'select',
      '#title'          => $this->t('State'),
      '#default_value'  => $member->state,
      '#options'        => $states,
    ];

    $form['member']['zip'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Zip'),
      '#default_value' => $member->zip,
    ];

    $form['member']['country'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Country'),
      '#default_value'  => $member->country,
      '#options'        => $countries,
    ];

    $form['member']['clubacp'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Club affiliation'),
      '#default_value'  => $member->clubacp,
      '#options'        => $clubs,
    ];

    // Get the official data
    $ofobj = new RusaOfficials(['key' => 'mid', 'val' => $mid]);
    $official = $ofobj->getOfficial($mid);
 
    if ($official) {

      $form['official'] = [
        '#type'   => 'details',
        '#title'  => $this->t('RUSA Official'),
        '#group'  => 'rusa',
      ];

      $form['official']['fname'] = [
        '#type'           => 'textfield',
        '#title'          => 'First name',
        '#default_value'  => $official->fname,
      ];

      $form['official']['sname'] = [
        '#type'           => 'textfield',
        '#title'          => 'Last name',
        '#default_value'  => $official->sname,
      ];

      $form['official']['email'] = [
        '#type'           => 'email',
        '#title'          => $this->t('E-mail'),
        '#default_value'  => $official->email,
      ];

      $form['official']['phone'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('Phone'),
        '#default_value'  => $official->phone,
      ];

      $form['official']['phone2'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('Phone 2'),
        '#default_value'  => $official->phone2,
      ];

      $form['official']['address'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('Address'),
        '#default_value'  => $official->address,
      ];

      $form['official']['city'] = [
        '#type'           => 'textfield',
        '#title'          => $this->t('City'),
        '#default_value'  => $official->city,
      ];

      $form['official']['state'] = [
        '#type'           => 'select',
        '#title'          => $this->t('State'),
        '#default_value'  => $official->state,
        '#options'        => $states,
      ];

      $form['official']['zip'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Zip'),
        '#default_value' => $official->zip,
      ];

      $form['official']['country'] = [
        '#type'           => 'select',
        '#title'          => $this->t('Country'),
        '#default_value'  => $official->country,
        '#options'        => $countries,
      ];
    }

    // Actions wrapper
    $form['actions'] = [
      '#type'   => 'actions',

      'cancel'  => [
        '#type'  => 'submit',
        '#value' => 'Cancel',
        '#attributes' => ['onclick' => 'if(!confirm("Do you really want to cancel?")){return false;}'],
      ],
      'submit' => [
        '#type'  => 'submit',
        '#value' => 'Submit',
      ],
   ];

    // Attach the Javascript and CSS, defined in rusa_rba.libraries.yml.
    // $form['#attached']['library'][] = 'rusa_api/chosen';
    $form['#attached']['library'][] = 'rusa_api/rusa_script';
    $form['#attached']['library'][] = 'rusa_api/rusa_style';

    return $form;
  }

 /**
   * @validateForm
   *
   * Required
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  } // End function verify


  /**
   * @submitForm
   *
   * Required
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getTriggeringElement();
    if ($action['#value'] == "Cancel") {
      $form_state->setRedirect('rusa_home');
    }
    else {
      $this->messenger->addMessage(t("Your changes have been saved."), $this->messenger::TYPE_STATUS);
    }
  }

} // End of class  
