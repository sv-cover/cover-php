#!/usr/bin/ruby
require 'optparse'
require 'ostruct'
require 'rexml/document'
@@commentreg = Regexp.new('/\*(([ \t]*\*[^\n]*?\n)*)[ \t]*\*/[ \t]*\n', Regexp::MULTILINE)
@@linereg = Regexp.new('([ \t]*\n)*([^\n]+)\n')
@@paramsreg = Regexp.new('[ \t]*(&?)\$([a-zA-Z_0-9]+)([ \t]*=[ \t]*([^, ]+))?')

@@classes = {}
@@groups = {}
@@templates = []

class DocClass
	attr_reader :name, :parent, :description, :uri, :children

	def initialize(name, parent, description = '')
		@name = name
		@parent = parent
		@uri = name.gsub(' ', '_') + '.html'
		@children = {}
	
		@description = ''
		
		description.split("\n").each do |line|
			line.strip!

			if line and not line.empty?
				@description += line + ' '
			else
				@description += "\n"
			end
		end
		
		@description.strip!
		@functions = []
	end
	
	def add_function(f)
		if f.name[0] != ?_
			@functions << f
		end
	end
	
	def get_functions
		return @functions.sort do |a,b|
			a.name.downcase <=> b.name.downcase
		end
	end
	
	def get_function(f)
		@functions.each do |func|
			if (func.name == f)
				return func
			end
		end
		
		return nil		
	end
	
	def include?(f)
		return get_function(f) != nil
	end
	
	def get_html
		html = "<h1>#{@name}</h1>\n"

		if @parent
			parent = self
			num = 0
		
			html += "<h2>Parents</h2>\n"

			while parent.parent
				num += 1
				parent = @@classes[parent.parent]
				html += "<div class=\"indent\"><a href=\"#{parent.uri}\">#{parent.name}</a>\n"
			end
		
			html += "<div class=\"indent\">Object" + "</div>\n" * (num + 1)
		end
		
		if @children
			html += "<h2>Children</h2>\n<div class=\"indent\">\n"

			@children.each do |name, child|
				html += "<a href=\"#{child.uri}\">#{child.name}</a><br/>\n"
			end
			
			html += "</div>\n"
		end
		
		html += "<h2>Synopsis</h2>"
		
		html += '<div class="messages"><table class="messages">'
		
		get_functions.each do |func|
			html += "<tr><td><a class=\"message\" href=\"##{func.name}\">#{func.name}</a></td><td>(#{func.get_params.join(', ')})</td></tr>\n"
		end
		
		html += '</table></div>'
		html += "<h2>Description</h2>\n" + html_encode(@description)
		
		html += "<h2>Details</h2>\n"
		
		get_functions.each do |func|
			html += func.get_html(@name)
			html += "<hr/>\n"
		end
		
		return html
	end
	
	def to_s
		return @name
	end	
end

class DocTemplate
	attr_reader :name, :uri
	
	def initialize(name, file)
		@name = name
		@uri = file
	end
	
	def get_html
		html = "<h1>#{@name}</h1>\n"
		href = "templates/#{@uri}"
		
		if FileTest.exists?(href)
			html += resolve_links(File.new(href, 'r').read)
		else
			html += '<div class="error">The template for this file could not be found!</div>'
			$stderr.puts("Template file #{href} could not be found!\n")
		end
		
		return html
	end
end

class DocGroup
	attr_reader :name, :docs, :uri, :children

	def initialize(name)
		if name
			@name = name
		else
			@name = 'Micellaneous'
		end
		
		@uri = @name.gsub(' ', '_') + '.html'
		@docs = []
		@children = {}
	end
	
	def add_doc(doc)
		@docs << doc
	end
	
	def include?(s)
		@docs.each do |doc|
			if doc.name == s
				return true
			end
		end
		
		return false
	end
	
	def get_docs
		return @docs.sort do |a,b|
			a.name.downcase <=> b.name.downcase
		end
	end
	
	def get_html
		html ="<h1>#{@name}</h1>\n"
		html += "<h2>Synopsis</h2>"
		
		html += '<div class="messages"><table class="messages">'
		
		get_docs.each do |func|
			html += "<tr><td><a class=\"message\" href=\"##{func.name}\">#{func.name}</a></td><td>(#{func.get_params.join(', ')})</td></tr>\n"
		end
		
		html += '</table></div>'
		html += "<h2>Details</h2>\n"
		
		get_docs.each do |func|
			html += func.get_html
			html += "<hr/>\n"
		end
		
		return html
	end
end

class DocFunction
	attr_reader :name, :description, :result, :group, :uri
	
	def initialize(name, params, doc)
		@name = name
		@result = nil
		@description = ''
		@group = nil
		@uri = name.gsub(' ', '_') + '.html'

		lines = doc.split("\n")
		nodelete = false

		# Parse the header
		while true and lines.length > 0
			if (opt = parse_option(lines[0]))
				lines.delete_at(0)
				
				if opt[0] == 'group'
					@group = opt[1]
				end
			else
				break
			end
		end

		# Parse description
		(0..lines.length - 1).each do |i|
			line = lines[i].strip
			 
			if line and line[0] != ?@
				@description += line + ' '			
			elsif not line
				@description += "\n"			
			else
				lines = lines[i..-1]
				break
			end
		end

		@description.strip!
		parse_params(params, lines)
	end
	
	def parse_option(s)
		return nil if s[0] != ?@

		pos = s.index(' ')
		
		return [s[1..-1], ''] if not pos
		return [s[1..pos-1], s[pos + 1..-1].strip]
	end
	
	def parse_params(params, lines)
		# params => [name, default, reference]
		@params = []
		pars = {}
		param = nil

		params.each do |item|
			ar = [item[0], item[1], item[2], '']
			@params << ar
			pars[item[0]] = ar
		end

		lines.each do |line|
			line.strip!

			if line and not line.empty?
				if line[0] == ?@
					if param
						pars[param][2].strip!
					end
					
					param = nil
					opt = parse_option(line)
					
					if opt
						if pars.include?(opt[0])
							param = opt[0]
							
							pars[param][3] = opt[1]
						elsif ['result', 'return'].include?(opt[0])
							param = 'result'
							pars[param] = [param, nil, nil, opt[1]]
						end
					end
				elsif param
					pars[param][3] += ' ' + line
				end
			elsif param
				pars[param][3] += "\n"
			end
		end
		
		if pars.include?('result')
			@result = pars['result'][3]
		end
	end
	
	def get_params
		result = []
		
		@params.each do |par|
			result << par[0]
		end
		
		return result
	end
	
	def get_html_params()
		pars = []
		
		@params.each do |par|
			item = par[0]
			
			if par[2] and not par[2].empty?
				item = "<span class=\"bold\">&amp;</span><span class=\"underline\">#{item}</span>"
			end			
			if par[1] and not par[1].empty?
				item = "<span class=\"italic\">#{item}</span> = <span class=\"bold\">#{par[1]}</span>"
			end
			
			pars << item
		end
		
		return pars.join(",\n")
	end
	
	def get_html_param_table()
		s = "<table class=\"arguments\">\n"
		
		@params.each do |par|
			name = par[0]
			
			if par[1] and not par[1].empty?
				name = "<span class=\"italic\">#{name}</span>"
			end
			if par[2] and not par[2].empty?
				name = "<span class=\"underline\">#{name}</span>"
			end
			
			s += "<tr><td valign=\"top\" class=\"param_name\">#{name}</td><td class=\"right\" valign=\"top\">:</td>\n<td class=\"param_desc\">#{resolve_links(par[3])}</td></tr>\n"
		end
		
		if @result
			s += "<tr><td valign=\"top\" class=\"param_name\"><span class=\"bold\">Returns</span></td><td>&nbsp;</td><td class=\"param_desc\">#{resolve_links(@result)}</td></tr>\n"
		end

		s += "</table>\n"
		
		return s
	end
	
	def get_html(namespace = nil)
		if namespace
			name = "#{namespace}::#{@name}"
		else
			name = @name
		end
		
		s = "<h3 id=\"#{@name}\">#{name}</h3>\n"
		
		s += "<div class=\"code\">\n"
		s += "#{name} (\n"
		s += get_html_params()		
		s += "\n)\n"
		s += "</div>\n"
		
		s += "<div class=\"description\">#{html_encode(@description)}</div>\n"
		s += get_html_param_table()
		
		return s
	end
end

def strip_comment(comment)
	return comment.gsub(/^(\/\*\*|[ \*\t])+[ \t]*(.*?)[ \t]*/, '\2 ').strip
end

def check_file(file)
	begin
		contents = File.new(file).read
	rescue
		print "Error while reading file: " + file + "\n"
		return
	end
	
	newclass = nil

	while ((m = @@commentreg.match(contents)))
		contents = contents[m.end(0)..-1]
		
		# Match the next line
		t = @@linereg.match(contents)
	
		if (t)
			contents = contents[t.end(0)..-1]
			line = t[2]
			
			c = /^[ \t]*class ([^ \t]+)([ \t]+extends[ \t]([^ \t]+))?/i.match(line)

			if c
				if c[2]
					newclass = DocClass.new(c[1], c[3], strip_comment(m[1]))
				else
					newclass = DocClass.new(c[1], nil, strip_comment(m[1]))
				end

				@@classes[newclass.name] = newclass
			else
				f = /function[ \t]+([^ \t]+)\((.*)\)/i.match(line)
				
				if f
					params = []
					pstring = f[2]
					
					while ((p = @@paramsreg.match(pstring)))
						params << [p[2], p[4], p[1]]
						pstring = pstring[p.end(0)..-1]
					end
					
					func = DocFunction.new(f[1], params, strip_comment(m[1]))
					
					if func.name[0] != ?_
						if newclass
							newclass.add_function(func)
						else
							if not @@groups.include?(func.group)
								@@groups[func.group] = DocGroup.new(func.group)
							end

							@@groups[func.group].add_doc(func)
						end
					end
				else
					$strerr.print("Not a function there! #{line}\n")
				end
			end
		end
	end
end

def read_toc(toc)
	if toc
		begin
			toc = File.new(toc).read
		rescue
			print "Could not read TOC file (#{toc})\n"
			exit
		end
	else
		toc = '<toc><generated type="classes" /></toc>'
	end
	
	return toc
end

def html_header(title)
	s = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>API :: ' + title + '</title>
		<link href="styles/style.css" type="text/css" rel="stylesheet" />
	</head>
	<body>
	'
	return s
end

def html_footer(title)
	
	return '<div class="footer">Generated by gendocs ' + Time.now.to_s + ' &nbsp;&nbsp;<a href="http://validator.w3.org/check?uri=referer"><img src="http://www.icecrew.nl/images/w3c_xhtml.png" alt="xhtml"/></a></div>
	</body></html>'
end

def get_link_index(uri, links)
	links.each_index do |index|
		if links[index][1] == uri
			return index
		end
	end
	
	return -1
end

def html_navigation(uri, links)
	index = get_link_index(uri, links)

	if index > 0
		prevlink = links[index - 1][1]
		prevtitle = links[index - 1][0]
	else
		prevlink = 'index.html'
		prevtitle = 'Index'
	end

	if index < links.length - 1
		nextlink = links[index + 1][1]
		nexttitle = links[index + 1][0]
	else
		nextlink = 'index.html'
		nexttitle = 'Index'
	end
	
	s = '<table class="navigation">
	<tr>
		<td class="left"><a href="' + prevlink + '"><img title="' + prevtitle + '" class="link" alt="prev" src="images/left.png" /></a> <a href="index.html"><img class="link" alt="home" src="images/home.png" /></a></td>
		<td class="nav_title">API :: ' + links[index][0] + '</td>
		<td class="right"><a href="' + nextlink + '"><img title="' + nexttitle + '" class="link" alt="next" src="images/right.png" /></a></td>
	</tr>
</table>'

	return s
end

def resolve_links(s)
	return s.gsub(/#([a-z_]+)(::([a-z_]+))?/i) do |item|
		res = "#{item}"

		if @@classes.include?($1)
			if $2
				if @@classes[$1].include?($3)
					res = "<a href=\"#{@@classes[$1].uri}##{@@classes[$1].get_function($3).name}\">#{$1 + $2}</a>"
				end
			
			else
				res = "<a href=\"#{@@classes[$1].uri}\">#{$1}</a>"
			end
		elsif @@groups.include?($1)
			if $2
				if @@groups[$1].include?($3)
					res = "<a href=\"#{@@groups[$1].uri}##{$3}\">#{@@groups[$1].name}::#{$3}</a>"
				end
			else
				res = "<a href=\"#{@@groups[$1].uri}\"}#{$1}</a>"
			end
		else
			@@templates.each do |template|
				if File.basename(template.uri, '.html') == $1
					res = "<a href=\"#{template.uri}\">#{template.name}</a>"
					break
				end
			end
		end
		
		res
	end
end

def html_encode(s)
	result = ''
	
	s.split("\n").each do |line|
		result += "<div class=\"paragraph\">#{resolve_links(line)}</div>\n"
	end
	
	return result
end

def parse_li_list(items)
	html = ''
	links = []

	items.each do |item|
		if item.class == Array
			h, l = parse_li_list(item)
			
			html += "<ul>\n" + h + "</ul>\n"
			links += l
		else
			html += "<li><a href=\"#{item.uri}\">#{item.name}</a></li>\n"
			links << [item.name, item.uri]
		end
	end
	
	return html, links
end

def parse_toc_page(page)
	links = []
	html = ''
	ending = ''

	if page.attributes.include?('type')
		type = page.attributes['type']
		items = nil
		
		if type == 'classes'
			html += "<li class=\"group\"><span class=\"bold\">Classes</span><ul>\n"
			items = sort_items(@@classes)
		elsif type == 'groups'
			html += "<li class=\"group\"><span class=\"bold\">Groups</span><ul>\n"
			items = sort_items(@@groups)
		end
		
		h, links = parse_li_list(items)
		
		html += h + "</ul>\n"
	else
		links << [page.elements['title'].text, page.elements['uri'].text]
		html += "<li><a href=\"#{page.elements['uri'].text}\">#{page.elements['title'].text}</a>\n"
		
		@@templates << DocTemplate.new(page.elements['title'].text, page.elements['uri'].text)
	end
	
	if page.elements['page']
		html += "<ul>\n"
		
		page.each_element('page') do |element|
			newlinks, newhtml = parse_toc_page(element)
			
			links += newlinks
			html += newhtml
		end
		
		html += "</ul>\n"
	end
	
	html += "</li>\n"

	return links, html
end

def write_toc(toc)
	doc = REXML::Document.new(toc)

	if doc.root.name != 'toc'
		print "Invalid TOC format"
		exit
	end
	
	links = [['Index', 'index.html']]
	html = ''

	doc.root.each_element('page') do |element|
		newlinks, newhtml = parse_toc_page(element)
			
		links += newlinks
		html += newhtml
	end

	f = File.new('api/html/index.html', 'w')
	f.puts(html_header('Index'))
	f.puts(html_navigation('index.html', links))
	f.puts("<ul>\n#{html}</ul>\n")
	f.puts(html_footer('Index'))
	f.close
	
	return links
end

def sort_items(items)
	if items.class == Hash
		values = items.values
	else
		values = items
	end

	values.sort! do |a,b|
		if a == nil
			-1
		elsif b == nil
			1
		else
			a.name.downcase <=> b.name.downcase
		end
	end

	# Now sort all the children
	list = []
	values.each_index do |i|
		list << values[i]

		if values[i].instance_variables.include?('@children')
			list << sort_items(values[i].children)
		end
	end
	
	return list
end

def resolve_class_tree
	items = @@classes
	
	items.each do |key,item|
		if item.parent and @@classes.include?(item.parent) and not item.children.include?(item.parent)
			@@classes[item.parent].children[item.name] = item
			@@classes.delete(key)
		end
	end
end

def generate_html(toc, files)
	toc = read_toc(toc)
	classes = []
	
	files.each do |file|
		check_file(file)
	end
	
	resolve_class_tree
	
	if !FileTest.exists?('api/html')
		Dir.mkdir('api/html')
	end
	
	`rm -f api/html/*.html`
	
	links = write_toc(toc)
	
	[@@classes, @@groups, @@templates].each do |items|
		values = sort_items(items)
		values.flatten!
		
		values.each_index do |index|
			item = values[index]
			href = item.uri
			f = File.new('api/html/' + href, 'w')
			f.puts(html_header(item.name))
			f.puts(html_navigation(href, links))
			
			f.puts(item.get_html)		
			f.puts(html_footer(item.name))
			f.close
		end
	end
	
	# Copy images
	if !FileTest.exists?('api/html/images')
		Dir.mkdir('api/html/images')
	end
	
	`rm -f api/html/images/*`
	`cp images/* api/html/images/`
	
	# Copy styles
	if !FileTest.exists?('api/html/styles')
		Dir.mkdir('api/html/styles')
	end
	
	`rm -f api/html/styles/*`
	`cp styles/* api/html/styles/`
end

def generate_xml(toc, files)
end

def parse_options()
	options = OpenStruct.new
	options.generate_html = false
	options.generate_xml = false
	options.toc = nil

	opts = OptionParser.new do |opts|
		opts.banner = "Usage: gendocs.rb [options] files"
		opts.separator ""
		opts.separator "Specific options:"
		
		opts.on("-t", "--toc FILE",
			"Use FILE as the table of contents") do |toc|
			options.toc = toc
		end
		
		opts.on(nil, "--html", "Generate html") do
			options.generate_html = true
		end
		
		opts.on(nil, "--xml", "Generate xml") do
			options.generate_xml = true
		end

		opts.separator ""
		opts.separator "Common options:"

		opts.on_tail("-h", "--help", "Show this message") do
			puts opts
			exit
		end
	end
	
	begin
		opts.parse!(ARGV)
	rescue OptionParser::InvalidOption => boom
		print boom.to_s + "\n"
		puts opts
		exit
	end
	
	return options
end

opts = parse_options

if ARGV.length == 0
	print "Please specify files to generate documentation from\n"
	exit
end
		
if ENV['srcdir']
	srcdir = ENV['srcdir']
else
	srcdir = './'
end

files = []	

ARGV.each do |file|
	files += Dir.glob(srcdir + file)
end

generate_html(opts.toc, files) if not opts.generate_html and not opts.generate_xml
generate_html(opts.toc, files) if opts.generate_html
generate_xml(opts.toc, files) if opts.generate_xml
	
