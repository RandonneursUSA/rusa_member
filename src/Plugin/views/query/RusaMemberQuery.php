<?php
/*
 * @file RusaMemberQuery.php
 *
 * Author: Paul Lieberman
 * Created: Feb-22-2018
 *
 * A Views Query Plugin to view member data
 *
 */

namespace Drupal\rusa_member\Plugin\views\query;

use Drupal\Core\Messenger;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\rusa_api\RusaMembers;
use Drupal\rusa_api\RusaClubs;
use Drupal\rusa_api\RusaOfficials;
use Drupal\rusa_api\RusaRegions;
use Drupal\rusa_api\RusaPermanents;
use Drupal\rusa_api\RusaResults;

/**
 * RUSA Views Query Plugin class
 *
 * @ViewsQuery(
 *   id = "rusa_member",
 *   title = @Translation("RUSA"),
 *   help = @Translation("Query against the RUSA backend.")
 * )
 */
class RusaMemberQuery extends QueryPluginBase {

  protected $where;

  /**
   * Constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition);
  }

  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }
    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }
    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  } 

  /**
   * {@inheritdoc}
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    // This is being called by our sort plugin
    if ($field) {
      $this->order = [
        'field' => $field,
        'order' => $order,
      ];
    }
  }


  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {

    // Okay some non Drupal hacks here for now.
    // There is a Drupal way to get the %user path, but it seems like overkill.
    $path  = $_SERVER['REQUEST_URI'];
    $parts = explode("/",$path);
    $uid   = $parts[2];

    // Back to Drupal land
    $user = \Drupal\user\Entity\User::load($uid);
    $mid  = $user->get('field_rusa_member_id')->value;

    if (empty($mid) || ! is_numeric($mid)){
      $messenger = \Drupal::messenger();
      $messenger->addMessage(t("Sorry no RUSA # for this user. " .
                         "You must edit the user and enter a RUSA # before this tab can display the data.") , 
                         $messenger::TYPE_WARNING);
      return;
    }

    // Get the clubs
    $clobj  = new RusaClubs();
    $clubs  = $clobj->getClubs();

    // Now get the member
    $memobj  = new RusaMembers(['key' => 'mid', 'val' => $mid]);
    $members = $memobj->getMembers();
    $member  = $members[$mid];

    // Get the club
    $clobj  = new RusaClubs(['key' => 'acpcode', 'val' => $member->clubacp]);
    $clubs  = $clobj->getClubs();

    // See if member is an official
    $offobj = new RusaOfficials(['key' => 'mid', 'val' => $mid]);
    if ($offobj->isOfficial($mid)) {
      $offobj->addTitles();
      $titles = $offobj->getTitles($mid);

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
          $positions[] = $title;
        }
      }
    }

    // If RBA get region
    if (in_array('Regional Brevet Administrator', $titles)) {
      $regobj  = new RusaRegions(['key' => 'rbaid', 'val' => $mid]);
      $regions = $regobj->getRegionsByRba($mid);
    }

    // If perm route owner
    if (in_array('Permanent Route Owner', $titles)) {
      $pobj   = new RusaPermanents(['key' => 'mid', 'val' => $mid]);
      $perms  = $pobj->getPermsByOwner($mid);
    }


    $row['mid']       = $member->mid;
    $row['fname']     = $member->fname;
    $row['sname']     = $member->sname;
    $row['email']     = $member->email;
    $row['address']   = $member->address;
    $row['city']      = $member->city;
    $row['state']     = $member->state;
    $row['club']      = $clubs[$member->clubacp]->name . " / " . $member->clubacp; 
    $row['birthdate'] = $member->birthdate;
    $row['joindate']  = $member->joindate;
    $row['expdate']   = $member->expdate;
  
    // Add positions
    if (!empty($positions)) {
      $row['title']   = implode("\n", $positions);
    }

    // Add committees
    if (!empty($coms)) {
      $row['committee'] = implode("\n", $coms);
    }

    // Add regions
    if (isset($regions)) {
      foreach ($regions as $regid => $region) {
        $regline .= $region->state . ':  ' . $region->city . "\n"; 
      }
      $row['regions'] = $regline;
    }

    // Add permanents
    if (isset($perms)) {
      foreach ($perms as $pid => $perm) {
        $permline .= "%TR%" . $perm->name . 
                     "%TD%" . $perm->dist . "km" .
                     "%TD%" . $perm->climbing . "'" .
                     "%TD%" . $perm->startcity . ", " . $perm->startstate .  
                     "%TD%" . $perm->description . 
                     "%ER%";
      }
      $row['perms'] = $permline;
    }

    // Add results
    $resobj  = new RusaResults(['key' => 'mid', 'val' => $mid]);
    $results = $resobj->getResults();
    foreach ($results as $rsid => $result) {
      // Cert No. Type   Km   Date  Route   Time
      $rsline .=  "%TR%" . $result->cert .
                  "%TD%" . $result->type .
                  "%TD%" . $result->dist .
                  "%TD%" . $result->date .
                  "%TD%" . $result->routename .
                  "%TD%" . $result->time .
                  "%ER%";
    }
    $row['results'] = $rsline;

    // 'index' key is required.
    $row['index']   = 0;
    $view->result[] = new ResultRow($row);
    $view->element['#attached']['library'][] = 'rusa_api/rusa_style';

  } // End execute

} // End Class 

