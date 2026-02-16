# frozen_string_literal: true

# The plans endpoints.
class App
  options '/plans' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/plans/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/plans' do
    now = Time.now
    from = params[:from] ? Time.parse(params[:from]) : now - (30 * 24 * 60 * 60)
    to = params[:to] ? Time.parse(params[:to]) : now + (1 * 24 * 60 * 60)

    all_plans = plans.to_a
    all_plans.select! { |p| p[:created_at] > from && p[:created_at] < to }

    status 200
    { data: all_plans }.to_json
  end

  post '/plans' do
    errors = PlanContract.new.call(plan_params).errors(full: true).to_h

    if errors.empty?
      plan_hash = {}
      plan_params.each do |k, v|
        plan_hash[k.to_sym] = v
      end

      new_plan = plan_repo.create(plan_hash)

      revision_hash = plan_hash
      revision_hash[:plan_id] = new_plan[:id]
      revision_hash[:number] = 0

      errors = RevisionContract.new.call(revision_hash).errors(full: true).to_h

      if errors.empty?
        revision_repo.create(revision_hash)
        updated_plan = plan_repo.plan_with_revisions(new_plan.locator).one!

        response.headers['Location'] =
          "#{request.scheme}://#{request.host}/plans/#{new_plan.locator}"
        status 201
        { data: updated_plan.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    else
      unprocessable_entity!(errors)
    end
  end

  get '/plans/:id' do
    if plan
      status 200
      { data: plan.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/plans/:id' do
    if plan.nil?
      resource_not_found
    else
      plan_hash = plan.to_h
      plan_params.each do |k, v|
        plan_hash[k.to_sym] = v
      end

      errors = PlanContract.new.call(plan_hash).errors(full: true).to_h

      if errors.empty?
        updated_plan = plan_repo.update(plan.id, plan_hash)

        status :ok
        { data: updated_plan.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/plans/:id' do
    if plan.nil?
      resource_not_found
    else
      plan_repo.delete(plan.id)
      status :no_content
    end
  end

  private

  def plan
    @plan ||= plan_repo.plan_with_revisions(params[:id]).one!
  rescue StandardError
    nil
  end

  def plans
    @plans ||= MAIN_CONTAINER.relations[:plans].call
  end

  def plan_params
    return params[:data]&.slice(:name, :location) unless params.empty?

    request.body.rewind
    check = JSON.parse(request.body.read)
    check['data']&.slice('name', 'location')
  end

  def plan_repo
    @plan_repo ||= PlanRepo.new(MAIN_CONTAINER)
  end
end
