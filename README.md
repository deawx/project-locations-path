== README.md == https://github.com/javierrey/project-locations- ==

== GPS Locations Path Example. 2015. By Javier Rey. javier.rey.eu@gmail.com ==

Description

    Web PHP script to clean wrong GPS entries from a source list of locations and
    timestamps, based on parameters speed and acceleration thresholds.

    After cleaning the locations path, it plots the resulting route over a map,
    using a stretched timescale to quickly complete the journey.

Usage:

    Web URL (replace hostname as appropriate):

        http://localhost/project-locations-path/?v=0&a=0&t=0

    All parameters set to 0 will use the internal default values:

    Speed v = 3.65, Acceleration a = 1.32, Timescale t = 15.

    Set other parameter values in the URL query and reload.

    Value -1 is reserved to set a huge threshold: No filtering, all entries are shown.

    Value -2 is reserved to set a zero threshold: Full filtering, only journey stops will show.

Requirements and dependencies

    This project requires Apache and PHP properly installed in the hosting system.

    Depending on the deployment location, host name and folder names, '.htaccess' files in
    the project root and the public folder might need to be updated accordingly.
