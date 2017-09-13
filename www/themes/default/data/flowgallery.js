/* Author: Florian Maul */

(function($) {

	/* ------------ PRIVATE functions ------------ */

	/** Utility function that returns a value or the defaultvalue if the value is null */
	var $nz = function(value, defaultvalue) {
		if( typeof (value) === undefined || value == null) {
			return defaultvalue;
		}
		return value;
	};
	
	/**
	 * Distribute a delta (integer value) to n items based on
	 * the size (width) of the items thumbnails.
	 * 
	 * @method calculateCutOff
	 * @property len the sum of the width of all thumbnails
	 * @property delta the delta (integer number) to be distributed
	 * @property items an array with items of one row
	 */
	var calculateCutOff = function(len, delta, items) {
		// resulting distribution
		var cutoff = [];
		var cutsum = 0;

		// distribute the delta based on the proportion of
		// thumbnail size to length of all thumbnails.
		for(var i in items) {
			var item = items[i];
			var fractOfLen = item.twidth / len;
			cutoff[i] = Math.floor(fractOfLen * delta);
			cutsum += cutoff[i];
		}

		// still more pixel to distribute because of decimal
		// fractions that were omitted.
		var stillToCutOff = delta - cutsum;
		while(stillToCutOff > 0) {
			for(i in cutoff) {
				// distribute pixels evenly until done
				cutoff[i]++;
				stillToCutOff--;
				if (stillToCutOff == 0) break;
			}
		}
		return cutoff;
	};
	
	/**
	 * Takes images from the items array (removes them) as 
	 * long as they fit into a width of maxwidth pixels.
	 *
	 * @method buildImageRow
	 */
	var buildImageRow = function(maxwidth, items) {
		var row = [], len = 0;
		
		// each image a has a 3px margin, i.e. it takes 6px additional space
		var marginsOfImage = 6;

		// Build a row of images until longer than maxwidth
		while(items.length > 0 && len < maxwidth) {
			var item = items.shift();
			row.push(item);
			len += (item.twidth + marginsOfImage);
		}

		// calculate by how many pixels too long?
		var delta = len - maxwidth;

		// if the line is too long, make images smaller
		if(row.length > 0 && delta > 0) {

			// calculate the distribution to each image in the row
			var cutoff = calculateCutOff(len, delta, row);

			for(var i in row) {
				var pixelsToRemove = cutoff[i];
				item = row[i];

				// move the left border inwards by half the pixels
				item.vx = Math.floor(pixelsToRemove / 2);

				// shrink the width of the image by pixelsToRemove
				item.vwidth = item.twidth - pixelsToRemove;
			}
		} else {
			// all images fit in the row, set vx and vwidth
			for(var i in row) {
				item = row[i];
				item.vx = 0;
				item.vwidth = item.twidth;
			}
		}

		return row;
	};
	
	/**
	 * Updates an exisiting tthumbnail in the image area. 
	 */
	var updateImageElement = function(item) {
		var overflow = item.el;
		var img = overflow.find("img:first");

		overflow.addClass('flow-image-container');
		overflow.css("width", "" + $nz(item.vwidth, 150) + "px");
		overflow.css("height", "" + $nz(item.theight, 150) + "px");

		img.css("margin-left", "" + (item.vx ? (-item.vx) : 0) + "px");
	};

	var resetImageElement = function(item) {
		item.el.removeClass('flow-image-container');

		item.el.css({
			'width': '',
			'height': ''
		});

		item.el.find('img:first').css({
			'margin-left': 0
		});
	};

	var indexElements = function(parent)
	{
		return parent.children('li').map(function() {
			var $thumb = $(this).find('img').first(),
				tw = parseInt($thumb.attr('width')),
				th = parseInt($thumb.attr('height'));

			// Scale height to 200
			var theight = 200,
				twidth = tw * theight / th;

			return {
				'twidth': twidth,
				'theight': theight,
				'title': '',
				'el': $(this)
			};
		}).get();
	}

	$.widget('cover.flowgallery', {
		_scheduled_update: null,

		_create: function() {
			this._items = indexElements($(this.element));
			this._update_on_resize = $.proxy(this._repaint, this);
			$(window).on('resize', this._update_on_resize);
			this.update();
		},

		update: function() {
			// reduce width by 1px due to layout problem in IE
			var containerWidth = this.element.width() - 1;
			
			var items = this._items.slice();

			// calculate rows of images which each row fitting into
			// the specified windowWidth.
			var rows = [];
			while(items.length > 0) {
				rows.push(buildImageRow(containerWidth, items));
			}  

			for(var r in rows) {
				for(var i in rows[r]) {
					var item = rows[r][i];
					updateImageElement(item);
				}
			}
		},

		_destroy: function() {
			$(window).off('resize', this._update_on_resize);
			for (var i in this._items)
				resetImageElement(this._items[i]);
		},

		_repaint: function() {
			clearTimeout(this._scheduled_update);
			this._scheduled_update = setTimeout($.proxy(this.update, this), 10);
		}
	});
})(jQuery);