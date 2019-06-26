<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class BullshitController extends Controller {
  public function index() {
    return "<pre>
                 .-----.
                /       `\
              _|_         |
             /   \        |
             '==='        |
             . ' .        |
            . : ' .       |
               '.         |
           . '    .       |
            .-\"\"\"-.       |
           /  \___ \      |
           |/`    \|      |
           (  a  a )      |
           |   _\ |     ... Who let you in here?
           )\  =  /       |
       _.-'  '---;        |
     /`           `-.     |
    |                \    |
    |    |   .  & .   \   |
    \    /      &   |  ;  |
    |   |           |  ;  |
    |   /\          /  |  |
    \   \ )   -:-  /\  \  |
     `.  `-.  -:-  | \  \_|
       '-.  `-.    (  './\`\
        / `'-. `\  |    \/_/
        |    \  |  |      |
        |    /'-\  /      |
         \   \   | |      |
          \   )_/\ |      |
           \      \|      |
            \      \      |
             '.     |     |
               /   /      |
              /  .';      |
            /`  /  |      |
           /   /   |      |
          |  .' \  |      |
          /  \  )  |      |
          \   \ /  '-.._  |
           '.ooO\__._.Ooo |
    </pre>";
  }

  public function randomKey() {
    return str_random(32);
  }

  public function ok() {
    return "OK";
  }
}
