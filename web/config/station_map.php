<?php

/** Austauschbarer Betreiber-Endpunkt, nie aus Requestdaten. Kein Offline-Prefetch. */
return ['tiles' => env('NOVA_MAP_TILES', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')];
