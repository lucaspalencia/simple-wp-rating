# Simple WP Rating Plugin #

WordPress Plugin for rating posts and order by average rating.

**Contributors:** lucaspalencia  
**Requires at least:** 4.1  
**Tested up to:** 4.4  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

## Admin Options (Tools -> Simple WP Rating): ##

* Posts per page for swr_posts shortcode / swr_get_posts method. (default: 10)
* Update post rating only when comment is approved: The post rating only will be update after comment is approved by admin. (default: false)

## Display posts by rating ##

### Shortocde ###

```php
[swr_posts]
```

### PHP ###

```php
$posts_swr = Simple_WP_Rating::swr_get_posts();

if( $posts_swr->have_posts() ) {

	while( $posts_swr->have_posts() ) {
		$posts_swr->the_post();

		//custom loop
	}

} 
wp_reset_postdata();

```

## Display post stars ##

### Shortcode ###

```php
[swr_stars]

```

### PHP ###

```php
Simple_WP_Rating::swr_show_post_stars();

```

