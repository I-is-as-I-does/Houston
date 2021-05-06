<?php 
/* This file is part of Houston | ExoProject | (c) 2021 I-is-as-I-does | MIT License */
namespace ExoProject\Houston;

interface Houston_i
{
  public function __construct($datatolog = null, $origin = null, $lvl = null, $configPath = null);
  public function validateAndSetConfig($configPath = null);
  public function handle($datatolog, $origin = null, $lvl = null);
}