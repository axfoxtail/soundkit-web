<?php
$request->get('/', array('uses' => 'home@index', 'secure' => false));

//admin
$request->get('admin', array('uses' => 'admin@index'));
$request->any('admin/settings', array('uses' => 'admin@settings'));
$request->any('admin/users', array('uses' => 'admin@users'));
$request->any('admin/roles', array('uses' => 'admin@roles'));
$request->any('admin/verification/request', array('uses' => 'admin@verify'));
$request->any('admin/user/action', array('uses' => 'admin@userAction'));
$request->any('admin/user/edit', array('uses' => 'admin@userEdit'));
$request->any('admin/genres', array('uses' => 'admin@genres'));
$request->any('admin/plugins', array('uses' => 'admin@plugins'));
$request->any('admin/tracks', array('uses' => 'admin@tracks'));
$request->any('admin/payments', array('uses' => 'admin@payments'));
$request->any('admin/payments/transfer', array('uses' => 'admin@bankTransfer'));

$request->any('admin/reports', array('uses' => 'admin@reports'));
$request->any('admin/ads', array('uses' => 'admin@ads'));
$request->any('admin/pages', array('uses' => 'admin@pages'));
$request->any('admin/page/add', array('uses' => 'admin@addPage'));
$request->any('admin/page/edit', array('uses' => 'admin@editPage'));
$request->any('admin/newsletter', array('uses' => 'admin@newsletter'));
$request->any('admin/design', array('uses' => 'admin@design'));
$request->any('admin/promote', array('uses' => 'admin@promote'));
$request->any('admin/playlists', array('uses' => 'admin@playlist'));
$request->any('admin/playlist/delete', array('uses' => 'admin@deletePlaylist'));
$request->any('admin/albums', array('uses' => 'admin@playlist'));
$request->any('admin/update/wave/images', array('uses' => 'admin@updateWave'));
$request->any('admin/update', array('uses' => 'admin@update'));


$request->any('logout', array('uses' => 'home@logout', 'secure' => false));
$request->any('login', array('uses' => 'home@login', 'secure' => false));
$request->any('signup', array('uses' => 'home@signup', 'secure' => false));
$request->any('auth/facebook', array('uses' => 'home@authFacebook', 'secure' => false));
$request->any('activate/account', array('uses' => 'user@activate', 'secure' => false));
$request->any('reset/password', array('uses' => 'user@reset', 'secure' => false));
$request->any('forgot', array('uses' => 'user@forgot', 'secure' => false));

$request->any('welcome', array('uses' => 'home@welcome'));
$request->any('welcome/load', array('uses' => 'home@welcomeLoad'));
$request->any('welcome/finish', array('uses' => 'home@welcomeFinish'));


$request->any('search', array('uses' => 'home@search', 'secure' => false));
$request->any('search/dropdown', array('uses' => 'home@searchDropdown', 'secure' => false));

$request->any('sitemap', array('uses' => 'home@sitemap', 'secure' => false));

$request->any('load/share', array('uses' => 'home@share', 'secure' => false));

$request->any('pro', array('uses' => 'home@pricing', 'secure' => false));
$request->any('trial/pro', array('uses' => 'home@trypro', 'secure' => false));

$request->any('notification/dropdown', array('uses' => 'user@notificationDropdown'));
$request->any('notifications', array('uses' => 'user@notifications'));
$request->any('notification/paginate', array('uses' => 'user@notificationPaginate'));
$request->any('check/notification', array('uses' => 'user@checkNotification'));

$request->any('charts', array('uses' => 'home@charts', 'secure' => false));
$request->any('charts/trending', array('uses' => 'home@charts', 'secure' => false));
$request->any('charts/top', array('uses' => 'home@charts', 'secure' => false));
$request->any('discover', array('uses' => 'home@discover', 'secure' => false));
$request->any('discover/latest', array('uses' => 'home@discover', 'secure' => false));
$request->any('discover/artists', array('uses' => 'home@discover', 'secure' => false));
$request->any('discover/albums', array('uses' => 'home@discover', 'secure' => false));
$request->any('discover/playlists', array('uses' => 'home@discover', 'secure' => false));
$request->any('discover/genre/{id}', array('uses' => 'home@discover', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any('upload', array('uses' => 'home@upload'));
$request->any('search/tags', array('uses' => 'home@searchTags'));

$request->any('page/{id}', array('uses' => 'home@page', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));

$request->any('upload/track', array('uses' => 'track@upload'));
$request->any('upload/track/wave', array('uses' => 'track@uploadWave'));
$request->any('upload/track/picture', array('uses' => 'track@uploadPicture'));
$request->any('load/track/player/buttons', array('uses' => 'track@loadPlayerButtons', 'secure' => false));
$request->any('track/play/{id}/{hash}', array('uses' => 'track@play', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+', 'hash' => '[a-zA-Z0-9\_\-]+'));
$request->any('track/load', array('uses' => 'track@load', 'secure' => false));
$request->any('player/load', array('uses' => 'track@playerLoad', 'secure' => false));
$request->any('track/paginate', array('uses' => 'track@paginate', 'secure' => false));
$request->any('track/download', array('uses' => 'track@downloadTrack', 'secure' => false));
$request->any('track/add/download', array('uses' => 'track@download', 'secure' => false));
$request->any('track/delete', array('uses' => 'track@deleteTrack'));
$request->any('playlist/delete', array('uses' => 'track@deletePlaylist'));
$request->any('track/add/later', array('uses' => 'track@addLater'));
$request->any('track/remove/later', array('uses' => 'track@removeLater'));
$request->any('track/set/views', array('uses' => 'track@setViews','secure' => false));
$request->any('track/people/paginate', array('uses' => 'track@paginatePeople', 'secure' => false));
$request->any('playlist/load', array('uses' => 'track@playlist'));
$request->any('track/tags', array('uses' => 'track@trackAsTags'));
$request->any('track/track-tags', array('uses' => 'track@trackAsTags'));
$request->any('playlist/add', array('uses' => 'track@addPlaylist'));
$request->any('load/playlist/form', array('uses' => 'track@playlistForm'));
$request->any('save/playlist/order', array('uses' => 'track@savePlaylistOrder'));
$request->any('download/album', array('uses' => 'track@downloadAlbum'));


$request->any('playlist/add/track', array('uses' => 'track@addPlaylistTrack'));
$request->any('track/{slug}', array('uses' => 'track@profile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
$request->any('track/{slug}/{other}', array('uses' => 'track@profile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+', 'other' => '[a-zA-Z0-9\_\-]+'));


$request->any('comment/add', array('uses' => 'track@addComment'));
$request->any('comment/time/load', array('uses' => 'track@commentTimeLoad', 'secure' => false));
$request->any('comment/delete', array('uses' => 'track@deleteComment'));
$request->any('comment/load', array('uses' => 'track@loadComment', 'secure' => false));
$request->any('comment/paginate', array('uses' => 'track@paginateComment', 'secure' => false));

$request->any('report/comment', array('uses' => 'track@reportComment'));
$request->any('report/track', array('uses' => 'track@reportTrack'));
$request->any('tr/a/{id}/details', array('uses' => 'track@trackDetail', 'secure' => false))->where(array('id' => '[0-9]+'));

$request->any('like/item', array('uses' => 'track@likeItem'));
$request->any('repost/item', array('uses' => 'track@repostItem'));


$request->any('collection/listen-later', array('uses' => 'home@collection'));
$request->any('collection/likes', array('uses' => 'home@collection'));
$request->any('collection/history', array('uses' => 'home@collection'));
$request->any('collection/playlists', array('uses' => 'home@collection'));
$request->any('collection/albums', array('uses' => 'home@collection'));

$request->any('save/theme/mode', array('uses' => 'home@saveThemeMode', 'secure' => false));

$request->any('clear/history', array('uses' => 'home@clearHistory'));

$request->any('playlist/paginate', array('uses' => 'track@playlistPaginate', 'secure' => false));

$request->any('artists/paginate', array('uses' => 'user@paginateArtists', 'secure' => false));

$request->any('settings', array('uses' => 'user@settings'));
$request->any('two/factor/auth', array('uses' => 'user@twoFactor', 'secure' => false));
$request->any('artist/verify', array('uses' => 'user@verify'));
$request->any('settings/{slug}', array('uses' => 'user@settings'))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));

$request->any('user/card', array('uses' => 'user@userCard', 'secure' => false));

$request->any('cronjob/run', array('uses' => 'cron@run', 'secure' => false));

$request->any('change/language', array('uses' => 'home@changeLanguage', 'secure' => false));


$request->any('set/{slug}', array('uses' => 'track@setProfile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
$request->any('set/{slug}/{other}', array('uses' => 'track@setProfile', 'secure' => false))->where(array('slug' => '[a-zA-Z0-9\_\-]+', 'other' => '[a-zA-Z0-9\_\-]+'));


$request->any('follow', array('uses' => 'user@follow'));
$request->any('block', array('uses' => 'user@block'));

$request->any('statistics', array('uses' => 'home@stats'));
$request->any('load/charts', array('uses' => 'home@loadCharts'));

$request->any('payment/method', array('uses' => 'payment@load'));
$request->any('payment/stripe', array('uses' => 'payment@stripe'));
$request->any('payment/stripe/hook', array('uses' => 'payment@stripeHook', 'secure' => false));
$request->any('payment/stripe/cancel', array('uses' => 'payment@stripeCancel', 'secure' => false));

$request->any('payment/paypal', array('uses' => 'payment@initPaypal'));
$request->any('payment/paypal/complete', array('uses' => 'payment@completePaypal'));

$request->any('payment/bank/transfer', array('uses' => 'payment@bankTransfer'));

$request->any('payment/mollie', array('uses' => 'payment@initMollie'));
$request->any('payment/mollie/verify', array('uses' => 'payment@verifyMollie'));
$request->any('payment/mollie/hook', array('uses' => 'payment@hookMollie'));


$request->any('message/form', array('uses' => 'message@form'));
$request->any('message/search', array('uses' => 'message@search'));
$request->any('messages', array('uses' => 'message@messages'));
$request->any('chat/paginate', array('uses' => 'message@paginate'));

$request->any('user/search', array('uses' => 'user@userSearch'));
$request->any('users/suggestion', array('uses' => 'user@userSuggests'));

//api routes
$request->any('api/{key}/login', array('uses' => 'api@login', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/signup', array('uses' => 'api@signup', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/check/auth', array('uses' => 'api@checkAuth', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/load/tracks', array('uses' => 'api@loadTracks', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/genres', array('uses' => 'api@getGenres', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/load/comments', array('uses' => 'api@loadComments', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/comment', array('uses' => 'api@addComment', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/remove/comment', array('uses' => 'api@removeComment', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/track/details', array('uses' => 'api@trackDetails', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/like/item', array('uses' => 'api@likeItem', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/repost/item', array('uses' => 'api@repostItem', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/navigate/player', array('uses' => 'api@navigatePlayer', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/get/my/playlists', array('uses' => 'api@getMyPlaylists', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/to/playlist', array('uses' => 'api@addToPlaylist', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/playlist', array('uses' => 'api@addPlaylist', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/get/download/detail', array('uses' => 'api@getTrackDownloadDetail', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/people/list', array('uses' => 'api@listPeople', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/list/playlist', array('uses' => 'api@listPlaylist', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/has/spotlight', array('uses' => 'api@hasSpotlight', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));

$request->any('api/{key}/store/purchased', array('uses' => 'api@getPurchased', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/video/list', array('uses' => 'api@listVideo', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/change/avatar', array('uses' => 'api@changeAvatar', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/account/save', array('uses' => 'api@saveAccount', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/user/detail', array('uses' => 'api@userDetail', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/user/follow', array('uses' => 'api@userFollow', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/notifications', array('uses' => 'api@notificationList', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/notification/delete', array('uses' => 'api@deleteNotification', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/message/lists', array('uses' => 'api@listMessages', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/chats', array('uses' => 'api@chats', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/chat/send', array('uses' => 'api@sendChat', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/price/detail', array('uses' => 'api@priceDetail', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/track/play', array('uses' => 'api@trackPlay', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/video/play', array('uses' => 'api@videoPlay', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/add/video/view', array('uses' => 'api@videoView', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/play/video', array('uses' => 'api@playVideo', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/suggest/videos', array('uses' => 'api@suggestVideos', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/pay', array('uses' => 'api@pay', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/social/signup', array('uses' => 'api@socialSignup', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/radio', array('uses' => 'api@radio', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/blogs', array('uses' => 'api@blogs', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/upload/picture', array('uses' => 'api@uploadPicture', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/setup', array('uses' => 'api@setup', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));
$request->any('api/{key}/activate/pro', array('uses' => 'api@activatePro', 'secure' => false))->where(array('key' => '[a-zA-Z0-9\_\-]+'));

$request->any('embed/track/{id}', array('uses' => 'home@embedCode', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any('embed/playlist/{id}', array('uses' => 'home@embedCode', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));

$request->any('{id}', array('uses' => 'user@profile', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+'));
$request->any('{id}/{slug}', array('uses' => 'user@profile', 'secure' => false))->where(array('id' => '[a-zA-Z0-9\_\-]+', 'slug' => '[a-zA-Z0-9\_\-]+'));
