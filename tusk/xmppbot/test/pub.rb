require 'xmpp4r'; require 'xmpp4r/pubsub/helper/servicehelper'
c = Jabber::Client.new(Jabber::JID.new('pub@localhost')); c.connect('127.0.0.1'); c.auth('pubpass')
h = Jabber::PubSub::ServiceHelper.new(c, 'pubsub.localhost')
node = '500-Mouthguard-Export'
begin; h.create_node(node, Jabber::PubSub::NodeConfig.new(node, {"pubsub#max_items"=>"20"})); rescue => e; warn "create: #{e.message}"; end
item = Jabber::PubSub::Item.new
entry = REXML::Element.new('entry')
{'plan'=>ARGV[0],'revision'=>ARGV[1]}.each { |k,v| e=REXML::Element.new(k); e.text=v; entry.add_element(e) }
item.add_element(entry)
puts h.publish_item_to(node, item)
c.close
