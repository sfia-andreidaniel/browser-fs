<?php

    $__OneDB_Default_LiveMatch__ = array(
        'soccerwayPath' => '',
        'streamerScope' => '',
        'externalURL'   => '',

        'team_A_Name'   => '',
        'team_B_Name'   => '',
        'team_A_id'     => '',
        'team_B_id'     => '',
        'match_id'      => '',
        
        'start_time'    => 0,
        'stop_time'     => 0
    );

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'OneDB_Article_plugin_Match.class.php';

    OneDB_RegisterBackendObject( array(
        'backendName'       => 'Match',
        'docType'           => 'Match',
        'icon'              => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAALGPC/xhBQAAABd0RVh0U29mdHdhcmUAUGFpbnQuTkVUIHYzLjW9RaPJAAACTElEQVQ4T2VT32vTcBCPbwZjN9NZRceYIiI6wVZEEMaoe1FhD6LzYQyRoQPxUcbwYS8bw18IU8FA9hcouiclkIW2GbVYKIEi1GWlbkMHXUeRxY7QLPW8+3bNuvTg4Pu9+9yH+7mP84kiipG2i+EHYl/06v6geILc29bm8oYWi21+S81e/7tl+GPYHwN5pU2QlYMHalp3F+RfvgDXdaFaLkPuyThox48C+VAJw+8hUY908GiMoxKIaebWTSCpVrZA7+nx7Dt+wu6SIDuxeiD1sAjW0hIjICnNz/sJQD9/VmZZqKIYXpHe/WsQaKEQFGZeecGNx+LEBGhdnYwIMyZSiglzmYEBiWrNP38K5a9JcJ0qWOYPyI6OQnbkHhjDQ2Dcuc16QbjywgKszMqMF30Sl382bZKjWfLTUy0pr314vwdDMYXXMyaXG3vsJC9fgj+G4QGSvVdaCLL3Rzz/2qePkOrvh8LbNw6nBtsdqovqK6fTUPn9CxRBaCHQTnazEigTJdjO/Nh8h8OP6TUQZ21vlCA3PgbKocAuCRIuTk1CZXXVC96JMTlcHql5hK5t10enJ0A/dwbip09BKRZjtsryT39mEmUQRgI2RjUQYGk2xMbO28V17+8jqI+RrbEgyJnBQfj+6CEDs1rn5gBHjKMahlIiwez2epFhSCnGW2dsBm/cHYrTrAlEaTeXRe9UX2/9NiwLcCJx7JHvHjqP8aloVC5++VyjO/AT0G7g9tXSN67JLcHNl6WEOiJ4jRKmaKI6pHrkgonkEvYr4j/l/7F3LNQpqdsZAAAAAElFTkSuQmCC',
        'defaultNamePrefix' => 'Match'
    ) );

?>