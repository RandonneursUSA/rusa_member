<?php

namespace Drupal\rusa_member\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rusa_api\RusaMembers;
use Drupal\rusa_api\RusaClubs;
use Drupal\rusa_api\RusaOfficials;
use Drupal\rusa_api\RusaRegions;
use Drupal\rusa_api\RusaPermanents;
use Drupal\rusa_api\RusaResults;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Messenger;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RusaMemberViewController
 *
 */
class RusaMemberViewController extends ControllerBase {

  protected $mid; // Current users member id

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user) {
    // Get logged in user's RUSA #
    $user = User::load($current_user->id());
    $this->mid  = $user->get('field_rusa_member_id')->getValue()[0]['value'];

    if (empty($this->mid)) {
      // This should never happen as they should get an access denied first
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t("You must be logged in and have a RUSA # to use this form."), $messenger::TYPE_ERROR);
    }

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
   * Display member info
   */
  public function info() {

    // Get the member
    $memobj  = new RusaMembers(['key' => 'mid', 'val' => $this->mid]);
    $members = $memobj->getMembers();
    $member  = $members[$this->mid];

    // Get the club
    $clobj  = new RusaClubs(['key' => 'acpcode', 'val' => $member->clubacp]);
    $clubs  = $clobj->getClubs();

    // See if member is an official
    $titles = [];
    $offobj = new RusaOfficials(['key' => 'mid', 'val' => $this->mid]);
    if ($offobj->isOfficial($this->mid)) {
      $offobj->addTitles();
      $titles = $offobj->getTitles($this->mid);

      // Split titles into positions and committees
      $positions  = [];
      $coms = [];
      foreach ($titles as $title) {
        $count = 0;
        $committee = str_replace("Committee: ", "", $title, $count);
        if ($count == 1) {
          $coms[] = $committee;
        }
        else {
            if ($title != 'Permanent Route Owner') {
                $positions[] = $title;
            }
        }
      }
    }

    // If RBA get region
    if (in_array('Regional Brevet Administrator', $titles)) {
      $regobj  = new RusaRegions(['key' => 'rbaid', 'val' => $this->mid]);
      $regions = $regobj->getRegionsByRba($this->mid);
    }

    // If perm route owner
    /*
    if (in_array('Permanent Route Owner', $titles)) {
      $pobj   = new RusaPermanents(['key' => 'mid', 'val' => $this->mid]);
      $perms  = $pobj->getPermsByOwner($this->mid);
    }
    */
    
    $output = [
      '#type'       => 'container',
      '#attributes' => ['class'   => ['rusa-info']],
      '#attached'   => ['library' => ['rusa_api/rusa_style']],
    ];

    $output['info'] = [
      '#type'   => 'table',
      '#rows'   => [
        [$this->t("RUSA #"),          $member->mid],
        [$this->t("First name"),      $member->fname],
        [$this->t("Middle name"),     $member->mname],
        [$this->t("Last name"),       $member->sname],
        [$this->t("E-mail"),          $member->email],
        [$this->t("Address"),         $member->address],
        [$this->t("City"),            $member->city],
        [$this->t("State"),           $member->state],
        [$this->t("Club"),            $clubs[$member->clubacp]->name . " / " . $member->clubacp],
        [$this->t("Birth date"),      $member->birthdate],
        [$this->t("Join date"),       $member->joindate],
        [$this->t("Expiration date"), $member->expdate],
      ],
      '#attributes' => ['class'   => ['rusa-table']],
    ];

    // Add positions
    if (!empty($positions)) {
      $output['positions'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Positions',
        '#items' => $positions,
        '#attributes' => ['class' => ['rusa-list']],
      ];
    }

    // Add committees
    if (!empty($coms)) {
      $output['committies'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Committees',
        '#items' => $coms,
        '#attributes' => ['class' => ['rusa-list']],
      ];
    }

    // Add regions
    if (isset($regions)) {
      $rnames =[];
      foreach ($regions as $regid => $region) {
         $rnames[] = $region->state . ':  ' . $region->city;
      }
      $output['regions'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => 'Regions',
        '#items' => $rnames,
        '#attributes' => ['class' => ['rusa-list']],
      ];
    }

    // Add permanents
    if (isset($perms)) {
  
      $output['permhead'] = [
        '#markup' =>  "<h3>Permanents</h3>",
      ];

      $rows = [];
      foreach ($perms as $pid => $perm) {
        $rows[] =  [
          $perm->name,
          $perm->dist,
          $perm->climbing,
          $perm->startcity . ", " . $perm->startstate,
          $perm->description,
        ];
      }

      $output['perms'] = [
        '#type'       => 'table',
        '#header'     => [
          $this->t("Name"),       
          $this->t("Distance Km"),
          $this->t("Climbing feet"),
          $this->t("Start"),
          $this->t("Description"),
        ],
        '#rows'       => $rows,
        '#attributes' => ['class' => ['rusa-table']],
      ];
    }

    return $output;

  }



} //EoC
