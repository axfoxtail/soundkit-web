<?php
return array(
  'header' => array(
      'title' => 'Header Colors',
      'colors' => array(
          'header-bg' => array(
              'title' => 'Header Background Color',
              'color' => '#fff',
              'type' => 'background',
              'target' => '.in-header'
          ),

          'header-border' => array(
              'title' => 'Header Border Color',
              'color' => '#E9ECEF',
              'type' => 'border-bottom',
              'target' => '.in-header'
          ),

          'search-bg' => array(
              'title' => 'Search Background Color',
              'color' => '#fff',
              'type' => 'background',
              'target' => '.searchbar'
          ),
          'search-input-color' => array(
              'title' => 'Search Input Color',
              'color' => '#000',
              'type' => 'color',
              'target' => '.searchbar input'
          ),

          'search-icon-color' => array(
              'title' => 'Search Icon Color',
              'color' => '#000',
              'type' => 'color',
              'target' => '.searchbar i'
          ),

          'header-link-color' => array(
              'title' => 'Header Link Color',
              'color' => '#000',
              'type' => 'color',
              'target' => '.in-header ul li > a:not(.btn)'
          ),

      )
  ),

    'sidebar' => array(
        'title' => 'Side Menu Colors',
        'colors' => array(
            'sidebar-bg' => array(
                'title' => 'SideBar Background Color',
                'color' => '#F3F5F6',
                'type' => 'background',
                'target' => '#sideHeader'
            ),


            'sidebar-submeun-color' => array(
                'title' => 'SideBar Sub Menu Link Color',
                'color' => '#000',
                'type' => 'color',
                'target' => '#sideHeader .nav .sub-menu'
            ),


            'sidebar-link-color' => array(
                'title' => 'SideBar Link Color',
                'color' => 'grey',
                'type' => 'color',
                'target' => '#sideHeader .nav .head'
            ),



            'sidebar-submeun-icon-border-color' => array(
                'title' => 'SideBar Sub Menu Icon Border Color',
                'color' => '#000',
                'type' => 'border',
                'target' => '#sideHeader .nav li a i'
            ),

            'sidebar-active-color' => array(
                'title' => 'SideBar Active Link Color',
                'color' => '#F90251',
                'type' => 'color',
                'target' => '#sideHeader .nav li .active, #sideHeader .nav li a i'
            ),

        )
    ),

    'buttons' => array(
        'title' => 'Buttons Color',
        'colors' => array(
            'button-color' =>  array(
                'title' => 'Button Text Color',
                'color' => '#fff',
                'type' => 'color',
                'target' => '.btn-primary,.btn-secondary'
            ),

            'button-primary' =>  array(
                'title' => 'Button Primary Color',
                'color' => '#F90251',
                'type' => 'background',
                'target' => '.btn-primary'
            ),

            'button-secondary' =>  array(
                'title' => 'Button Secondary Color',
                'color' => '#868E95',
                'type' => 'background',
                'target' => '.btn-secondary'
            ),
        )
    ),

    'player' => array(
        'title' => 'Music Player Color',
        'colors' => array(


            'player-bg' =>  array(
                'title' => 'Music Player Background Color',
                'color' => '#F4F9FC',
                'type' => 'background',
                'target' => '.sound-container'
            ),

            'player-item-color' => array(
                'title' => 'Music Player Control Color',
                'color' => '#686868',
                'type' => 'color',
                'target' => '.sound-container .middle-content a, .sound-container .play-icon'
            ),
            'wave_color' => array(
                'title' => 'Music Player Wave Color',
                'color' => '#0F0710',
                'type' => 'color',
                'target' => ''
            ),
            'wave_colored' => array(
                'title' => 'Music Player Wave Active Color',
                'color' => '#F90251',
                'type' => 'color',
                'target' => ''
            )
        )
    ),

    'misc' => array(
        'title' => 'Others Color',
        'colors' => array(


            'colored' =>  array(
                'title' => 'Primary Color',
                'color' => '#ff5533',
                'type' => 'color',
                'target' => '.colored'
            ),

            'colored-bg' =>  array(
                'title' => 'Primary Color',
                'color' => '#ff5533',
                'type' => 'background',
                'target' => '.colored-bg'
            ),

            'scroller' =>  array(
                'title' => 'Browser Scroller Color',
                'color' => '#636e72',
                'type' => 'background',
                'target' => '::-webkit-scrollbar-thumb'
            ),

            'pro-badge' =>  array(
                'title' => 'Pro Badge Background Color',
                'color' => '#636E72',
                'type' => 'background',
                'target' => '.pro-badge'
            ),
        )
    )
);