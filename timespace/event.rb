class Event

  def self.make
    if @events.nil?
      @events = []
      text = File.open('events.txt').read
      text.each_line do |line|
        e = line.split(",")
        if e and e[1] and e[2] and e[3]
          @events << [e[1].strip, e[2].strip, e[3].strip]
        else
          puts "*** Something is wrong with #{line}"
        end
      end
    end

    @events
  end

end
