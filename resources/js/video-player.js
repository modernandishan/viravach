/**
 * Vidstack player entry, loaded only by views that actually render a
 * <media-player> (currently the public company page's intro video box).
 * Kept out of app.js so the many pages with no video never pay for
 * the player bundle or its stylesheets.
 */
import 'vidstack/player/styles/default/theme.css';
import 'vidstack/player/styles/default/layouts/video.css';
import 'vidstack/player';
import 'vidstack/player/layouts';
import 'vidstack/player/ui';
