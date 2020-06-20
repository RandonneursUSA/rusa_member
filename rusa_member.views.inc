<?php

function rusa_member_views_data(){
  $data = [];

  // Base data.
  // The format of the array is usually $data[table_name]['table'],

  // members
  $data['member']['table']['group'] = t('RUSA Member');
  $data['member']['table']['base'] = [
    'title' => t('RUSA member'),
    'help'  => t('Data from the various tables'),
    'query_id' => 'rusa_member',
  ];

  $data['member']['sname'] = [
    'title'  => t('Last Name'),
    'help'   => t('Last name.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['fname'] = [
    'title'  => t('First Name'),
    'help'   => t('First name.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['email'] = [
    'title'  => t('E-mail'),
    'help'   => t('E-mail.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['address'] = [
    'title'  => t('Address'),
    'help'   => t('Address.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['city'] = [
    'title'  => t('City'),
    'help'   => t('City.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['state'] = [
    'title'  => t('State'),
    'help'   => t('State.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['mid'] = [
    'title'  => t('Member ID'),
    'help'   => t('Member ID.'),
    'field'  => ['id' => 'numeric'],
  ];

  $data['member']['club'] = [
    'title'  => t('Club'),
    'help'   => t('Club.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['birthdate'] = [
    'title'  => t('Birth date'),
    'help'   => t('Birth date.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['joindate'] = [
    'title'  => t('Join date'),
    'help'   => t('Join date.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['expdate'] = [
    'title'  => t('Expiration date'),
    'help'   => t('Expiration date.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['title'] = [
    'title'  => t('Title'),
    'help'   => t('Title(s)'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['committee'] = [
    'title'  => t('Committees'),
    'help'   => t('Committees'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['regions'] = [
    'title'  => t('Regions'),
    'help'   => t('Regions.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['perms'] = [
    'title'  => t('Permanents'),
    'help'   => t('Permanents.'),
    'field'  => ['id' => 'standard'],
  ];

  $data['member']['results'] = [
    'title'  => t('Results'),
    'help'   => t('Results.'),
    'field'  => ['id' => 'standard'],
  ];

  return $data;
}
